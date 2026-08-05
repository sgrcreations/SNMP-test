<?php

namespace Modules\Dashboard\Services;

use App\Core\Enums\DeviceStatus;
use App\Core\Enums\DeviceType;
use Illuminate\Support\Carbon;
use Modules\Alerts\Models\Alert;
use Modules\Devices\Models\Device;
use Modules\Devices\Repositories\Contracts\DeviceRepositoryInterface;
use Modules\Interfaces\Models\DeviceInterface;
use Modules\Interfaces\Models\DeviceOnu;
use Modules\Settings\Services\SettingService;

class DashboardService
{
    public function __construct(
        private readonly DeviceRepositoryInterface $devices,
        private readonly SettingService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $deviceStats = array_merge(
            $this->devices->countByStatus(),
            $this->devices->countByReachability(),
        );

        $avgCpu = Device::query()->whereNotNull('last_cpu')->avg('last_cpu');
        $avgMem = Device::query()->whereNotNull('last_memory')->avg('last_memory');
        $avgTemp = Device::query()->whereNotNull('last_temperature')->avg('last_temperature');

        $uplinks = DeviceInterface::query()
            ->with('device:id,name,ip_address')
            ->where('is_uplink', true)
            ->orderByDesc('utilization')
            ->get();

        $rxBps = $uplinks->sum(fn (DeviceInterface $i) => (float) ($i->rx_bps ?? 0));
        $txBps = $uplinks->sum(fn (DeviceInterface $i) => (float) ($i->tx_bps ?? 0));
        $throughputBps = $rxBps + $txBps;
        $capacityBps = $uplinks->sum(fn (DeviceInterface $i) => (float) ($i->speed ?? 0));
        $capacityUsedPct = $capacityBps > 0 ? round(($throughputBps / $capacityBps) * 100, 1) : null;
        $peakUtil = $uplinks->max('utilization');

        $portsDown = DeviceInterface::query()
            ->where('oper_status', 'down')
            ->where(function ($q): void {
                $q->where('port_role', '!=', 'pon')
                    ->orWhereNull('port_role');
            })
            ->where('name', 'not like', '%NULL%')
            ->where('name', 'not like', '%Loop%')
            ->count();

        $portsDownDevices = (int) DeviceInterface::query()
            ->where('oper_status', 'down')
            ->where('name', 'not like', '%NULL%')
            ->selectRaw('count(distinct device_id) as c')
            ->value('c');

        $onuOnline = DeviceOnu::query()->where('status', 'online')->count();
        $onuOffline = DeviceOnu::query()->where('status', 'offline')->count();
        $onuTotal = DeviceOnu::query()->count();
        $onuCriticalOptical = DeviceOnu::query()
            ->whereNotNull('rx_power_dbm')
            ->where('rx_power_dbm', '<', -28)
            ->count();

        $typeBreakdown = collect(DeviceType::cases())->mapWithKeys(function (DeviceType $type) {
            $total = Device::query()->where('device_type', $type)->count();
            $online = Device::query()
                ->where('device_type', $type)
                ->where('reachability', DeviceStatus::Online)
                ->count();

            return [$type->value => [
                'label' => $type->label(),
                'total' => $total,
                'online' => $online,
            ]];
        })->filter(fn (array $row) => $row['total'] > 0)->all();

        $hotDevices = Device::query()
            ->whereNotNull('last_cpu')
            ->orderByDesc('last_cpu')
            ->limit(6)
            ->get(['id', 'name', 'ip_address', 'last_cpu', 'last_memory', 'last_temperature', 'reachability']);

        $hotPorts = DeviceInterface::query()
            ->with('device:id,name')
            ->whereNotNull('utilization')
            ->orderByDesc('utilization')
            ->limit(8)
            ->get();

        $openAlerts = Alert::query()
            ->with('device:id,name')
            ->where('status', 'open')
            ->latest('raised_at')
            ->limit(8)
            ->get();

        $providerSplit = $uplinks->map(function (DeviceInterface $iface) use ($throughputBps) {
            $portBps = (float) ($iface->rx_bps ?? 0) + (float) ($iface->tx_bps ?? 0);
            $share = $throughputBps > 0 ? round(($portBps / $throughputBps) * 100, 1) : 0;

            return [
                'device' => $iface->device?->name ?? 'Device',
                'port' => $iface->name,
                'label' => ($iface->description ?: $iface->name),
                'bps' => $portBps,
                'bps_label' => $this->formatBps($portBps),
                'share' => $share,
                'utilization' => $iface->utilization,
                'device_id' => $iface->device_id,
            ];
        })->sortByDesc('bps')->values();

        $lastPoll = Device::query()->max('last_polled_at');
        $total = (int) ($deviceStats['total'] ?? 0);
        $online = (int) ($deviceStats['online'] ?? 0);

        return [
            'stats' => [
                'total_devices' => $total,
                'active_devices' => $deviceStats['active'] ?? 0,
                'online' => $online,
                'offline' => $deviceStats['offline'] ?? 0,
                'unknown' => $deviceStats['unknown'] ?? 0,
                'health_pct' => $total > 0 ? round(($online / $total) * 100) : 0,
                'open_alerts' => Alert::query()->where('status', 'open')->count(),
                'avg_cpu' => $avgCpu !== null ? round((float) $avgCpu, 1) : null,
                'avg_memory' => $avgMem !== null ? round((float) $avgMem, 1) : null,
                'avg_temperature' => $avgTemp !== null ? round((float) $avgTemp, 1) : null,
                'ports_down' => $portsDown,
                'ports_down_devices' => $portsDownDevices,
                'uplink_count' => $uplinks->count(),
                'throughput_bps' => $throughputBps,
                'throughput_label' => $this->formatBps($throughputBps),
                'download_label' => $this->formatBps($rxBps),
                'upload_label' => $this->formatBps($txBps),
                'capacity_bps' => $capacityBps,
                'capacity_label' => $this->formatBps($capacityBps),
                'capacity_used_pct' => $capacityUsedPct,
                'peak_util' => $peakUtil !== null ? round((float) $peakUtil, 1) : null,
                'onu_online' => $onuOnline,
                'onu_offline' => $onuOffline,
                'onu_total' => $onuTotal,
                'onu_critical_optical' => $onuCriticalOptical,
                'last_poll' => $lastPoll ? Carbon::parse($lastPoll)->diffForHumans() : null,
            ],
            'provider_split' => $providerSplit->take(5)->values(),
            'provider_split_total' => $providerSplit->count(),
            'type_breakdown' => $typeBreakdown,
            'hot_devices' => $hotDevices,
            'hot_ports' => $hotPorts,
            'open_alerts_list' => $openAlerts,
            'recent_devices' => $this->devices->search([], 8),
            'polling_enabled' => (bool) $this->settings->get('polling_enabled', true),
        ];
    }

    private function formatBps(float $bps): string
    {
        if ($bps <= 0) {
            return '0 bps';
        }
        if ($bps >= 1_000_000_000) {
            return number_format($bps / 1_000_000_000, 2).' Gbps';
        }
        if ($bps >= 1_000_000) {
            return number_format($bps / 1_000_000, 2).' Mbps';
        }
        if ($bps >= 1_000) {
            return number_format($bps / 1_000, 2).' Kbps';
        }

        return number_format($bps, 0).' bps';
    }
}
