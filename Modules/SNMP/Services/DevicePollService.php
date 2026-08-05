<?php

namespace Modules\SNMP\Services;

use App\Core\Enums\DeviceStatus;
use App\Core\Enums\DeviceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Alerts\Models\Alert;
use Modules\Alerts\Services\AlertEvaluationService;
use Modules\Devices\Models\Device;
use Modules\Interfaces\Models\DeviceInterface;
use Modules\Interfaces\Models\DeviceOnu;
use Modules\Interfaces\Models\DeviceVlan;
use Modules\Metrics\Models\DeviceMetric;
use Modules\Metrics\Models\DeviceStatusEvent;
use Modules\Metrics\Models\InterfaceMetric;
use Modules\Metrics\Models\PingSample;
use Modules\Metrics\Models\PollLog;
use Modules\Settings\Services\SettingService;
use Throwable;

class DevicePollService
{
    public function __construct(
        private readonly SNMPService $snmp,
        private readonly SettingService $settings,
        private readonly AlertEvaluationService $alerts,
        private readonly HuaweiOltCollector $oltCollector,
        private readonly PingProbeService $ping,
        private readonly VlanCollector $vlans,
    ) {}

    /**
     * @return array{success: bool, message: string, metrics?: array<string, mixed>}
     */
    public function poll(Device $device): array
    {
        $startedAt = now();
        $startedMs = (int) floor(microtime(true) * 1000);
        $previousReachability = $device->reachability?->value;
        $pingSample = null;

        try {
            $pingSample = $this->ping->probe($device);
            $snapshot = $this->snmp->readTrafficCounters($device);
            $detectedType = DeviceType::detectFromDescription(
                $snapshot['description'] ?? null,
                $device->vendor?->value
            );

            $oltData = ['pon_ports' => [], 'onus' => []];
            if ($detectedType === DeviceType::Olt || $device->device_type === DeviceType::Olt) {
                $oltData = $this->oltCollector->collect($device, $snapshot['interfaces']);
            }

            $vlanRows = $this->vlans->collect($device, $snapshot['interfaces']);
            $onuOnline = collect($oltData['onus'])->where('status', 'online')->count();
            $onuTotal = count($oltData['onus']);
            if ($onuTotal === 0) {
                $onuOnline = (int) collect($oltData['pon_ports'])->sum('onu_online');
                $onuTotal = (int) collect($oltData['pon_ports'])->sum('onu_total');
            }

            DB::transaction(function () use ($device, $snapshot, $startedAt, $detectedType, $oltData, $vlanRows, $pingSample, $onuOnline, $onuTotal): void {
                DeviceMetric::query()->create([
                    'device_id' => $device->id,
                    'cpu' => $snapshot['cpu'],
                    'memory' => $snapshot['memory'],
                    'temperature' => $snapshot['temperature'],
                    'rx_bytes' => $snapshot['rx_bytes'],
                    'tx_bytes' => $snapshot['tx_bytes'],
                    'uptime' => $snapshot['uptime'],
                    'onu_online' => $onuOnline ?: null,
                    'onu_total' => $onuTotal ?: null,
                    'recorded_at' => $startedAt,
                ]);

                PingSample::query()->create([
                    'device_id' => $device->id,
                    'latency_ms' => $pingSample['latency_ms'],
                    'jitter_ms' => $pingSample['jitter_ms'],
                    'packet_loss_pct' => $pingSample['packet_loss_pct'],
                    'packets_sent' => $pingSample['packets_sent'],
                    'packets_received' => $pingSample['packets_received'],
                    'recorded_at' => $startedAt,
                ]);

                foreach ($snapshot['interfaces'] as $row) {
                    $existing = DeviceInterface::query()
                        ->where('device_id', $device->id)
                        ->where('if_index', $row['if_index'])
                        ->first();

                    $rates = $this->calculateRates($existing, $row, $startedAt);
                    $role = $this->classifyPortRole($row['name'], $row['description'] ?? '');

                    /** @var DeviceInterface $interface */
                    $interface = DeviceInterface::query()->updateOrCreate(
                        [
                            'device_id' => $device->id,
                            'if_index' => $row['if_index'],
                        ],
                        [
                            'name' => $row['name'],
                            'description' => $row['description'],
                            'oper_status' => $row['oper_status'],
                            'speed' => $row['speed'],
                            'rx_bytes' => $row['rx_bytes'],
                            'tx_bytes' => $row['tx_bytes'],
                            'errors' => $row['errors'],
                            'utilization' => $rates['utilization'],
                            'rx_bps' => $rates['rx_bps'],
                            'tx_bps' => $rates['tx_bps'],
                            'port_role' => $existing?->is_uplink ? 'uplink' : $role,
                            'is_uplink' => (bool) ($existing?->is_uplink ?? $role === 'uplink'),
                            'onu_online' => $oltData['pon_ports'][$row['if_index']]['onu_online'] ?? 0,
                            'onu_total' => $oltData['pon_ports'][$row['if_index']]['onu_total'] ?? 0,
                            'rx_power_dbm' => $oltData['pon_ports'][$row['if_index']]['rx_power_dbm'] ?? null,
                            'tx_power_dbm' => $oltData['pon_ports'][$row['if_index']]['tx_power_dbm'] ?? null,
                            'temperature' => $oltData['pon_ports'][$row['if_index']]['temperature'] ?? null,
                            'last_polled_at' => $startedAt,
                        ]
                    );

                    InterfaceMetric::query()->create([
                        'device_id' => $device->id,
                        'device_interface_id' => $interface->id,
                        'rx_bytes' => $row['rx_bytes'],
                        'tx_bytes' => $row['tx_bytes'],
                        'errors' => $row['errors'],
                        'utilization' => $rates['utilization'],
                        'recorded_at' => $startedAt,
                    ]);
                }

                $this->syncOnus($device, $oltData['onus'], $startedAt);
                $this->syncVlans($device, $vlanRows);
                $this->ensureUplinkMapped($device);

                $device->forceFill([
                    'hostname' => $snapshot['hostname'] ?: $device->hostname,
                    'location' => $snapshot['location'] ?: $device->location,
                    'manufacturer' => $device->manufacturer ?: $device->vendor?->label(),
                    'device_type' => $device->device_type === DeviceType::Generic || ! $device->device_type
                        ? $detectedType
                        : $device->device_type,
                    'reachability' => DeviceStatus::Online,
                    'interface_count' => count($snapshot['interfaces']),
                    'last_cpu' => $snapshot['cpu'],
                    'last_memory' => $snapshot['memory'],
                    'last_temperature' => $snapshot['temperature'],
                    'sys_uptime' => $this->formatUptime($snapshot['uptime'] ?? null),
                    'last_polled_at' => $startedAt,
                    'last_seen_at' => $startedAt,
                ])->save();
            });

            $device->refresh();
            $this->alerts->evaluate($device);

            $duration = ((int) floor(microtime(true) * 1000)) - $startedMs;

            PollLog::query()->create([
                'device_id' => $device->id,
                'success' => true,
                'duration_ms' => max(0, $duration),
                'interfaces_count' => count($snapshot['interfaces']),
                'message' => 'Polled successfully',
                'meta' => [
                    'cpu' => $snapshot['cpu'],
                    'memory' => $snapshot['memory'],
                    'onus' => count($oltData['onus']),
                    'vlans' => count($vlanRows),
                    'latency_ms' => $pingSample['latency_ms'],
                ],
                'started_at' => $startedAt,
                'finished_at' => now(),
            ]);

            $this->recordStatusEvent($device, 'poll', 'success', 'SNMP poll succeeded', sprintf(
                '%d interfaces · %d VLANs · latency %s ms',
                count($snapshot['interfaces']),
                count($vlanRows),
                $pingSample['latency_ms'] ?? 'n/a'
            ), $startedAt, [
                'duration_ms' => $duration,
                'packet_loss_pct' => $pingSample['packet_loss_pct'],
            ]);

            if ($previousReachability !== DeviceStatus::Online->value) {
                $this->recordStatusEvent(
                    $device,
                    'reachability',
                    'success',
                    'Device came online',
                    "Reachability changed from {$previousReachability} to online",
                    $startedAt
                );
            }

            return [
                'success' => true,
                'message' => 'Polled successfully',
                'metrics' => [
                    'cpu' => $snapshot['cpu'],
                    'memory' => $snapshot['memory'],
                    'interfaces' => count($snapshot['interfaces']),
                    'onus' => count($oltData['onus']),
                    'vlans' => count($vlanRows),
                    'latency_ms' => $pingSample['latency_ms'],
                ],
            ];
        } catch (Throwable $e) {
            Log::channel('snmp')->error('Device poll failed', [
                'device_id' => $device->id,
                'ip' => $device->ip_address,
                'error' => $e->getMessage(),
            ]);

            // Still capture ping even when SNMP fails — useful for Network Quality.
            try {
                $pingSample = $pingSample ?? $this->ping->probe($device);
                PingSample::query()->create([
                    'device_id' => $device->id,
                    'latency_ms' => $pingSample['latency_ms'],
                    'jitter_ms' => $pingSample['jitter_ms'],
                    'packet_loss_pct' => $pingSample['packet_loss_pct'],
                    'packets_sent' => $pingSample['packets_sent'],
                    'packets_received' => $pingSample['packets_received'],
                    'recorded_at' => $startedAt,
                ]);
            } catch (Throwable) {
                // ignore ping failures inside error path
            }

            $device->forceFill([
                'reachability' => DeviceStatus::Offline,
                'last_polled_at' => $startedAt,
            ])->save();

            Alert::query()->firstOrCreate(
                [
                    'device_id' => $device->id,
                    'type' => 'device_offline',
                    'status' => 'open',
                ],
                [
                    'severity' => 'critical',
                    'title' => "{$device->name} offline",
                    'message' => $e->getMessage(),
                    'raised_at' => $startedAt,
                ]
            );

            PollLog::query()->create([
                'device_id' => $device->id,
                'success' => false,
                'duration_ms' => max(0, ((int) floor(microtime(true) * 1000)) - $startedMs),
                'interfaces_count' => 0,
                'message' => $e->getMessage(),
                'started_at' => $startedAt,
                'finished_at' => now(),
            ]);

            $this->recordStatusEvent($device, 'poll', 'critical', 'SNMP poll failed', $e->getMessage(), $startedAt);

            if ($previousReachability !== DeviceStatus::Offline->value) {
                $this->recordStatusEvent(
                    $device,
                    'reachability',
                    'critical',
                    'Device went offline',
                    "Reachability changed from {$previousReachability} to offline",
                    $startedAt
                );
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{rx_bps: ?string, tx_bps: ?string, utilization: ?float}
     */
    private function calculateRates(?DeviceInterface $existing, array $row, $now): array
    {
        if (! $existing || ! $existing->last_polled_at) {
            return ['rx_bps' => null, 'tx_bps' => null, 'utilization' => null];
        }

        $seconds = max(1, $existing->last_polled_at->diffInSeconds($now));
        $rxDelta = max(0, (float) $row['rx_bytes'] - (float) $existing->rx_bytes);
        $txDelta = max(0, (float) $row['tx_bytes'] - (float) $existing->tx_bytes);
        $rxBps = ($rxDelta * 8) / $seconds;
        $txBps = ($txDelta * 8) / $seconds;

        $utilization = null;
        if (! empty($row['speed']) && (int) $row['speed'] > 0) {
            $utilization = round((($rxBps + $txBps) / (int) $row['speed']) * 100, 2);
        }

        return [
            'rx_bps' => (string) round($rxBps, 2),
            'tx_bps' => (string) round($txBps, 2),
            'utilization' => $utilization,
        ];
    }

    private function classifyPortRole(string $name, string $description): string
    {
        $hay = strtolower($name.' '.$description);

        // Avoid false positives like "Ponnamali" — require GPON/EPON/PON token boundaries.
        if (
            preg_match('/\b(gpon|epon|xpon)\b/', $hay)
            || preg_match('/\bpon[-_]?\d+\b/', $hay)
            || preg_match('/(^|[^a-z])pon([^a-z]|$)/', $hay)
        ) {
            return 'pon';
        }

        if (str_contains($hay, 'uplink') || str_contains($hay, 'wan') || preg_match('/\bether1\b/', $hay)) {
            return 'uplink';
        }

        return 'access';
    }

    /**
     * @param  array<int, array<string, mixed>>  $onus
     */
    private function syncOnus(Device $device, array $onus, $seenAt): void
    {
        if ($onus === []) {
            return;
        }

        DeviceOnu::query()->where('device_id', $device->id)->delete();

        foreach ($onus as $onu) {
            DeviceOnu::query()->create([
                'device_id' => $device->id,
                'device_interface_id' => $onu['device_interface_id'] ?? null,
                'serial' => $onu['serial'] ?? null,
                'description' => $onu['description'] ?? null,
                'pon_port' => $onu['pon_port'] ?? null,
                'onu_id' => $onu['onu_id'] ?? null,
                'status' => $onu['status'] ?? 'unknown',
                'rx_power_dbm' => $onu['rx_power_dbm'] ?? null,
                'tx_power_dbm' => $onu['tx_power_dbm'] ?? null,
                'distance_m' => $onu['distance_m'] ?? null,
                'temperature' => $onu['temperature'] ?? null,
                'customer' => $onu['customer'] ?? null,
                'last_seen_at' => $seenAt,
            ]);
        }
    }

    /**
     * @param  array<int, array{vlan_id: int, name: ?string, status: string, member_ports: int}>  $vlanRows
     */
    private function syncVlans(Device $device, array $vlanRows): void
    {
        DeviceVlan::query()->where('device_id', $device->id)->delete();

        foreach ($vlanRows as $vlan) {
            DeviceVlan::query()->create([
                'device_id' => $device->id,
                'vlan_id' => $vlan['vlan_id'],
                'name' => $vlan['name'],
                'status' => $vlan['status'],
                'member_ports' => $vlan['member_ports'],
            ]);
        }
    }

    /**
     * Auto-map a device uplink when the operator has not marked one yet.
     * Prefers physical ports that carry the most VLAN subinterfaces / traffic.
     */
    private function ensureUplinkMapped(Device $device): void
    {
        if ($device->interfaces()->where('is_uplink', true)->exists()) {
            return;
        }

        $physical = $device->interfaces()
            ->where('oper_status', 'up')
            ->get()
            ->filter(fn (DeviceInterface $iface) => $this->isPhysicalPort($iface->name));

        if ($physical->isEmpty()) {
            $physical = $device->interfaces()
                ->get()
                ->filter(fn (DeviceInterface $iface) => $this->isPhysicalPort($iface->name));
        }

        if ($physical->isEmpty()) {
            return;
        }

        $allNames = $device->interfaces()->pluck('name');

        $best = $physical
            ->map(function (DeviceInterface $iface) use ($allNames) {
                $prefix = $iface->name.'.';
                $children = $allNames->filter(fn (string $name) => str_starts_with($name, $prefix))->count();
                $bytes = (float) $iface->rx_bytes + (float) $iface->tx_bytes;

                return [
                    'iface' => $iface,
                    'score' => ($children * 1_000_000_000_000) + $bytes,
                ];
            })
            ->sortByDesc('score')
            ->first();

        if (! $best) {
            return;
        }

        /** @var DeviceInterface $iface */
        $iface = $best['iface'];
        $iface->update([
            'is_uplink' => true,
            'port_role' => 'uplink',
        ]);
    }

    private function isPhysicalPort(string $name): bool
    {
        $lower = strtolower($name);

        if (
            str_contains($lower, 'null')
            || str_contains($lower, 'loopback')
            || str_contains($lower, 'inloop')
            || str_contains($lower, 'vlanif')
            || str_starts_with($lower, 'vlan')
        ) {
            return false;
        }

        // Exclude VLAN subinterfaces (Eth0/0/1.100)
        if (preg_match('/\.\d+$/', $name)) {
            return false;
        }

        return (bool) preg_match('/(ethernet|eth|giga|xge|100ge|ge-|te-|fo-|port)/i', $name);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function recordStatusEvent(
        Device $device,
        string $category,
        string $severity,
        string $title,
        ?string $message,
        $occurredAt,
        array $meta = [],
    ): void {
        DeviceStatusEvent::query()->create([
            'device_id' => $device->id,
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'meta' => $meta ?: null,
            'occurred_at' => $occurredAt,
        ]);
    }

    private function formatUptime(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return $raw;
        }

        $seconds = (int) floor(((float) $raw) / 100);
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $mins = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return sprintf('%dd %02dh %02dm %02ds', $days, $hours, $mins, $secs);
    }
}
