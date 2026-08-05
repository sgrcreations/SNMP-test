<?php

namespace Modules\Devices\Services;

use App\Core\Enums\DeviceStatus;
use App\Core\Enums\DeviceType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Alerts\Models\Alert;
use Modules\Devices\Models\Device;
use Modules\Metrics\Models\DeviceMetric;
use Modules\Metrics\Models\DeviceStatusEvent;
use Modules\Metrics\Models\InterfaceMetric;
use Modules\Metrics\Models\PingSample;

class DeviceMonitorService
{
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
            'latency_ms' => $latestPing?->latency_ms,
            'jitter_ms' => $latestPing?->jitter_ms,
            'packet_loss_pct' => $latestPing?->packet_loss_pct,
        ];
    }

    /**
     * @return array{categories: array<int, string>, cpu: array<int, float|null>, memory: array<int, float|null>, temperature: array<int, float|null>}
     */
    public function metricSeries(Device $device, string $range = '24h'): array
    {
        $from = $this->rangeStart($range);

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
     * @return array{categories: array<int, string>, rx_mbps: array<int, float>, tx_mbps: array<int, float>}
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

        // Fallback: busiest physical up port (so charts work before next poll auto-maps).
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
