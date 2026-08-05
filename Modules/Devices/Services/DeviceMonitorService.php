<?php

namespace Modules\Devices\Services;

use App\Core\Enums\DeviceStatus;
use App\Core\Enums\DeviceType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Alerts\Models\Alert;
use Modules\Devices\Models\Device;
use Modules\Metrics\Models\DeviceMetric;
use Modules\Metrics\Models\DeviceMetricRollup;
use Modules\Metrics\Models\DeviceStatusEvent;
use Modules\Metrics\Models\InterfaceMetric;
use Modules\Metrics\Models\PingSample;
use Modules\Settings\Services\SnmpAgentClient;
use Throwable;

class DeviceMonitorService
{
    public function __construct(
        private readonly SnmpAgentClient $agent,
    ) {}

    /**
     * @return array<string, int>
     */
    public function inventoryStats(): array
    {
        return [
            'total' => Device::query()->count(),
            'online' => Device::query()->where('reachability', DeviceStatus::Online)->count(),
            'offline' => Device::query()->where('reachability', DeviceStatus::Offline)->count(),
            'routers' => Device::query()->where('device_type', DeviceType::Router)->count(),
            'switches' => Device::query()->where('device_type', DeviceType::Switch)->count(),
            'olts' => Device::query()->where('device_type', DeviceType::Olt)->count(),
            'open_alerts' => Alert::query()->where('status', 'open')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(Device $device): array
    {
        $interfaces = $device->interfaces()->orderBy('if_index')->get();
        $up = $interfaces->where('oper_status', 'up')->count();
        $pon = $interfaces->where('port_role', 'pon');
        $onus = $device->onus()->get();
        $vlans = $device->vlans()->get();
        $latestPing = $device->pingSamples()->latest('recorded_at')->first();

        return [
            'interfaces_total' => $interfaces->count(),
            'interfaces_up' => $up,
            'interfaces_down' => $interfaces->where('oper_status', 'down')->count(),
            'port_usage' => $interfaces->count() > 0 ? round(($up / $interfaces->count()) * 100, 1) : 0,
            'vlans_total' => $vlans->count(),
            'vlans_active' => $vlans->where('status', 'active')->count(),
            'cpu' => $device->last_cpu,
            'memory' => $device->last_memory,
            'temperature' => $device->last_temperature,
            'uptime' => $device->sys_uptime,
            'open_alerts' => $device->alerts()->where('status', 'open')->count(),
            'pon_up' => $pon->where('oper_status', 'up')->count(),
            'pon_total' => $pon->count(),
            'onu_online' => $onus->where('status', 'online')->count(),
            'onu_offline' => $onus->where('status', 'offline')->count(),
            'onu_total' => $onus->count(),
            'uplinks_up' => $interfaces->where('is_uplink', true)->where('oper_status', 'up')->count(),
            'uplinks_total' => $interfaces->where('is_uplink', true)->count(),
            'errors_total' => (int) $interfaces->sum('errors'),
            'latency_ms' => $latestPing?->latency_ms,
            'jitter_ms' => $latestPing?->jitter_ms,
            'packet_loss_pct' => $latestPing?->packet_loss_pct,
        ];
    }

    /**
     * Live switch/router fabric snapshot for Overview charts (no CPU/mem).
     *
     * @return array{
     *   status: array{up: int, down: int, other: int},
     *   top_util: list<array{name: string, utilization: float|null, oper_status: string, id: int}>,
     *   top_errors: list<array{name: string, errors: int, oper_status: string, id: int}>
     * }
     */
    public function fabricSnapshot(Device $device): array
    {
        $interfaces = $device->relationLoaded('interfaces')
            ? $device->interfaces
            : $device->interfaces()->orderBy('if_index')->get();

        $physical = $interfaces->filter(function ($iface) {
            $n = strtolower((string) $iface->name);

            return ! str_contains($n, 'null')
                && ! str_contains($n, 'loop')
                && ! str_contains($n, 'inloop');
        });

        $up = $physical->where('oper_status', 'up')->count();
        $down = $physical->where('oper_status', 'down')->count();

        return [
            'status' => [
                'up' => $up,
                'down' => $down,
                'other' => max(0, $physical->count() - $up - $down),
            ],
            'top_util' => $physical
                ->filter(fn ($i) => $i->utilization !== null)
                ->sortByDesc('utilization')
                ->take(8)
                ->values()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'utilization' => $i->utilization,
                    'oper_status' => $i->oper_status,
                ])->all(),
            'top_errors' => $physical
                ->filter(fn ($i) => (int) $i->errors > 0)
                ->sortByDesc('errors')
                ->take(8)
                ->values()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'errors' => (int) $i->errors,
                    'oper_status' => $i->oper_status,
                ])->all(),
        ];
    }

    /**
     * Mbps / errors time series for one interface (popup chart).
     *
     * @return array{categories: array<int, string>, rx_mbps: array<int, float>, tx_mbps: array<int, float>, errors: array<int, int>, util: array<int, float|null>}
     */
    public function interfaceTrafficSeries(Device $device, int $interfaceId, string $range = '24h'): array
    {
        $from = $this->rangeStart($range);
        $iface = $device->interfaces()->whereKey($interfaceId)->first();
        if (! $iface) {
            return ['categories' => [], 'rx_mbps' => [], 'tx_mbps' => [], 'errors' => [], 'util' => []];
        }

        $rows = InterfaceMetric::query()
            ->where('device_interface_id', $interfaceId)
            ->where('recorded_at', '>=', $from)
            ->orderBy('recorded_at')
            ->get();

        $categories = [];
        $rx = [];
        $tx = [];
        $errors = [];
        $util = [];
        $prevRx = null;
        $prevTx = null;
        $prevAt = null;

        foreach ($rows as $row) {
            $at = $row->recorded_at;
            if ($prevAt && $prevRx !== null) {
                $seconds = max(1, $prevAt->diffInSeconds($at));
                $categories[] = $at?->format('H:i') ?? '';
                $rx[] = round((((max(0, (float) $row->rx_bytes - $prevRx)) * 8) / $seconds) / 1_000_000, 3);
                $tx[] = round((((max(0, (float) $row->tx_bytes - $prevTx)) * 8) / $seconds) / 1_000_000, 3);
                $errors[] = (int) $row->errors;
                $util[] = $row->utilization;
            }
            $prevRx = (float) $row->rx_bytes;
            $prevTx = (float) $row->tx_bytes;
            $prevAt = $at;
        }

        return [
            'categories' => $categories,
            'rx_mbps' => $rx,
            'tx_mbps' => $tx,
            'errors' => $errors,
            'util' => $util,
            'interface' => [
                'id' => $iface->id,
                'name' => $iface->name,
                'rx_bps' => $iface->rx_bps,
                'tx_bps' => $iface->tx_bps,
            ],
        ];
    }

    /**
     * Polling profile for UI (interval, source, next due).
     *
     * @return array{interval_seconds: int, interval_label: string, source: string, source_label: string, last_polled_at: ?string, next_due_at: ?string, next_due_label: string, live_refresh_seconds: int}
     */
    public function pollingProfile(Device $device): array
    {
        $seconds = max(30, (int) $device->polling_interval);
        $viaAgent = $this->agent->configured();
        $nextDue = $device->last_polled_at
            ? $device->last_polled_at->copy()->addSeconds($seconds)
            : null;

        return [
            'interval_seconds' => $seconds,
            'interval_label' => $this->humanInterval($seconds),
            'source' => $viaAgent ? 'snmp-agent' : 'laravel',
            'source_label' => $viaAgent ? 'snmp-agent (hot metrics)' : 'Laravel (local SNMP)',
            'last_polled_at' => $device->last_polled_at?->toDateTimeString(),
            'next_due_at' => $nextDue?->toDateTimeString(),
            'next_due_label' => $nextDue
                ? ($nextDue->isPast() ? 'Due now' : 'Next due '.$nextDue->diffForHumans())
                : 'Not polled yet',
            'live_refresh_seconds' => max(15, min($seconds, 60)),
        ];
    }

    /**
     * @return array{categories: array<int, string>, cpu: array<int, float|null>, memory: array<int, float|null>, temperature: array<int, float|null>, source: string}
     */
    public function metricSeries(Device $device, string $range = '24h'): array
    {
        $from = $this->rangeStart($range);
        $useRollups = in_array($range, ['7d', '30d'], true);

        if ($useRollups) {
            $period = $range === '30d' ? '1h' : '5m';
            $rollupSeries = $this->rollupMetricSeries($device, $from, $period);
            if (count($rollupSeries['categories']) > 0) {
                return $rollupSeries + ['source' => 'rollup'];
            }
        }

        if ($this->agent->configured()) {
            try {
                $rows = $this->agent->deviceMetrics(
                    $device,
                    $from->utc()->toIso8601String(),
                    now()->utc()->toIso8601String(),
                    $this->limitForRange($range),
                );

                // Agent returns newest-first; chart needs chronological order.
                $rows = array_reverse($rows);

                $categories = [];
                $cpu = [];
                $memory = [];
                $temperature = [];
                foreach ($rows as $row) {
                    $at = isset($row['recorded_at']) ? Carbon::parse((string) $row['recorded_at']) : null;
                    $categories[] = $at ? $at->format($range === '1h' ? 'H:i:s' : 'H:i') : '';
                    $cpu[] = isset($row['cpu']) ? (float) $row['cpu'] : null;
                    $memory[] = isset($row['memory']) ? (float) $row['memory'] : null;
                    $temperature[] = isset($row['temperature']) ? (float) $row['temperature'] : null;
                }

                return [
                    'categories' => $categories,
                    'cpu' => $cpu,
                    'memory' => $memory,
                    'temperature' => $temperature,
                    'source' => 'snmp-agent',
                ];
            } catch (Throwable) {
                // Fall through to Laravel history (legacy / offline agent).
            }
        }

        $rows = DeviceMetric::query()
            ->where('device_id', $device->id)
            ->where('recorded_at', '>=', $from)
            ->orderBy('recorded_at')
            ->get();

        return [
            'categories' => $rows->map(fn (DeviceMetric $m) => $m->recorded_at?->format('H:i'))->all(),
            'cpu' => $rows->map(fn (DeviceMetric $m) => $m->cpu)->all(),
            'memory' => $rows->map(fn (DeviceMetric $m) => $m->memory)->all(),
            'temperature' => $rows->map(fn (DeviceMetric $m) => $m->temperature)->all(),
            'source' => 'laravel',
        ];
    }

    /**
     * @return array{categories: array<int, string>, latency: array<int, float|null>, jitter: array<int, float|null>, loss: array<int, float|null>}
     */
    public function qualitySeries(Device $device, string $range = '24h'): array
    {
        $from = $this->rangeStart($range);

        $rows = PingSample::query()
            ->where('device_id', $device->id)
            ->where('recorded_at', '>=', $from)
            ->orderBy('recorded_at')
            ->get();

        return [
            'categories' => $rows->map(fn (PingSample $m) => $m->recorded_at?->format('H:i'))->all(),
            'latency' => $rows->map(fn (PingSample $m) => $m->latency_ms)->all(),
            'jitter' => $rows->map(fn (PingSample $m) => $m->jitter_ms)->all(),
            'loss' => $rows->map(fn (PingSample $m) => $m->packet_loss_pct)->all(),
        ];
    }

    /**
     * @return array{categories: array<int, string>, online: array<int, int|null>, total: array<int, int|null>, availability: array<int, float|null>}
     */
    public function onuAvailabilitySeries(Device $device, string $range = '24h'): array
    {
        $from = $this->rangeStart($range);

        $rows = DeviceMetric::query()
            ->where('device_id', $device->id)
            ->where('recorded_at', '>=', $from)
            ->whereNotNull('onu_total')
            ->orderBy('recorded_at')
            ->get();

        return [
            'categories' => $rows->map(fn (DeviceMetric $m) => $m->recorded_at?->format('H:i'))->all(),
            'online' => $rows->map(fn (DeviceMetric $m) => $m->onu_online)->all(),
            'total' => $rows->map(fn (DeviceMetric $m) => $m->onu_total)->all(),
            'availability' => $rows->map(function (DeviceMetric $m) {
                if (! $m->onu_total) {
                    return null;
                }

                return round(((int) $m->onu_online / (int) $m->onu_total) * 100, 1);
            })->all(),
        ];
    }

    /**
     * @return array{categories: array<int, string>, rx_mbps: array<int, float>, tx_mbps: array<int, float>, mapped?: bool}
     */
    public function uplinkTrafficSeries(Device $device, string $range = '24h'): array
    {
        $from = $this->rangeStart($range);
        $uplinkIds = $this->resolveUplinkInterfaceIds($device);

        if ($uplinkIds->isEmpty()) {
            return ['categories' => [], 'rx_mbps' => [], 'tx_mbps' => [], 'mapped' => false];
        }

        $rows = InterfaceMetric::query()
            ->whereIn('device_interface_id', $uplinkIds)
            ->where('recorded_at', '>=', $from)
            ->orderBy('recorded_at')
            ->get()
            ->groupBy(fn (InterfaceMetric $m) => $m->recorded_at?->format('Y-m-d H:i'));

        $categories = [];
        $rx = [];
        $tx = [];
        $prevRx = null;
        $prevTx = null;
        $prevAt = null;

        foreach ($rows as $bucket => $group) {
            $sumRx = (float) $group->sum(fn ($g) => (float) $g->rx_bytes);
            $sumTx = (float) $group->sum(fn ($g) => (float) $g->tx_bytes);
            $at = Carbon::createFromFormat('Y-m-d H:i', $bucket);

            if ($prevAt && $prevRx !== null) {
                $seconds = max(1, $prevAt->diffInSeconds($at));
                $categories[] = $at->format('H:i');
                $rx[] = round(((($sumRx - $prevRx) * 8) / $seconds) / 1_000_000, 3);
                $tx[] = round(((($sumTx - $prevTx) * 8) / $seconds) / 1_000_000, 3);
            }

            $prevRx = $sumRx;
            $prevTx = $sumTx;
            $prevAt = $at;
        }

        return [
            'categories' => $categories,
            'rx_mbps' => $rx,
            'tx_mbps' => $tx,
            'mapped' => true,
        ];
    }

    /**
     * @return Collection<int, int|string>
     */
    private function resolveUplinkInterfaceIds(Device $device): Collection
    {
        $uplinkIds = $device->interfaces()->where('is_uplink', true)->pluck('id');

        if ($uplinkIds->isNotEmpty()) {
            return $uplinkIds;
        }

        $uplinkIds = $device->interfaces()->where('port_role', 'uplink')->pluck('id');

        if ($uplinkIds->isNotEmpty()) {
            return $uplinkIds;
        }

        $candidate = $device->interfaces()
            ->where('oper_status', 'up')
            ->where('name', 'not like', '%.%')
            ->where('name', 'not like', '%NULL%')
            ->where('name', 'not like', '%Loop%')
            ->orderByRaw('(CAST(rx_bytes AS REAL) + CAST(tx_bytes AS REAL)) DESC')
            ->value('id');

        return $candidate ? collect([$candidate]) : collect();
    }

    /**
     * @return Collection<int, DeviceStatusEvent>
     */
    public function statusTimeline(Device $device, int $limit = 80): Collection
    {
        return DeviceStatusEvent::query()
            ->where('device_id', $device->id)
            ->latest('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{mapped: Collection<int, Device>, unmapped: Collection<int, Device>}
     */
    public function mapDevices(): array
    {
        $all = Device::query()->orderBy('name')->get();

        return [
            'mapped' => $all->filter(fn (Device $d) => $d->latitude !== null && $d->longitude !== null)->values(),
            'unmapped' => $all->filter(fn (Device $d) => $d->latitude === null || $d->longitude === null)->values(),
        ];
    }

    /**
     * @return array{categories: array<int, string>, cpu: array<int, float|null>, memory: array<int, float|null>, temperature: array<int, float|null>}
     */
    private function rollupMetricSeries(Device $device, Carbon $from, string $period): array
    {
        $rows = DeviceMetricRollup::query()
            ->where('device_id', $device->id)
            ->where('period', $period)
            ->where('bucket_at', '>=', $from)
            ->orderBy('bucket_at')
            ->get();

        return [
            'categories' => $rows->map(fn (DeviceMetricRollup $m) => $m->bucket_at?->format($period === '1h' ? 'm-d H:i' : 'H:i'))->all(),
            'cpu' => $rows->map(fn (DeviceMetricRollup $m) => $m->cpu_avg)->all(),
            'memory' => $rows->map(fn (DeviceMetricRollup $m) => $m->memory_avg)->all(),
            'temperature' => $rows->map(fn (DeviceMetricRollup $m) => $m->temperature_avg)->all(),
        ];
    }

    private function humanInterval(int $seconds): string
    {
        if ($seconds % 3600 === 0) {
            $h = intdiv($seconds, 3600);

            return $h === 1 ? 'every 1 hour' : "every {$h} hours";
        }
        if ($seconds % 60 === 0) {
            $m = intdiv($seconds, 60);

            return $m === 1 ? 'every 1 minute' : "every {$m} minutes";
        }

        return "every {$seconds}s";
    }

    private function limitForRange(string $range): int
    {
        return match ($range) {
            '1h' => 240,
            '7d' => 2000,
            '30d' => 2000,
            default => 1500,
        };
    }

    private function rangeStart(string $range): Carbon
    {
        return match ($range) {
            '1h', '6h' => now()->subHours((int) str_replace('h', '', $range) ?: 6),
            '7d', '1d', '3d' => now()->subDays(match ($range) {
                '1d' => 1,
                '3d' => 3,
                default => 7,
            }),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };
    }
}
