<?php

namespace Modules\Dashboard\Services;

use Modules\Devices\Models\Device;
use Modules\Devices\Repositories\Contracts\DeviceRepositoryInterface;
use Modules\Metrics\Models\DeviceMetric;
use Modules\Alerts\Models\Alert;
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

        $recentDevices = $this->devices->search([], 5);

        $latestMetrics = DeviceMetric::query()
            ->selectRaw('AVG(cpu) as avg_cpu, AVG(memory) as avg_memory, AVG(temperature) as avg_temperature')
            ->where('recorded_at', '>=', now()->subHour())
            ->first();

        $lastPoll = Device::query()->max('last_polled_at');

        return [
            'stats' => [
                'total_devices' => $deviceStats['total'] ?? 0,
                'active_devices' => $deviceStats['active'] ?? 0,
                'online' => $deviceStats['online'] ?? 0,
                'offline' => $deviceStats['offline'] ?? 0,
                'unknown' => $deviceStats['unknown'] ?? 0,
                'open_alerts' => Alert::query()->where('status', 'open')->count(),
                'avg_cpu' => $latestMetrics?->avg_cpu !== null ? round((float) $latestMetrics->avg_cpu, 1).'%' : null,
                'avg_memory' => $latestMetrics?->avg_memory !== null ? round((float) $latestMetrics->avg_memory, 1).'%' : null,
                'avg_temperature' => $latestMetrics?->avg_temperature !== null ? round((float) $latestMetrics->avg_temperature, 1).'°C' : null,
                'bandwidth_mbps' => null,
                'last_poll' => $lastPoll ? \Illuminate\Support\Carbon::parse($lastPoll)->diffForHumans() : null,
            ],
            'recent_devices' => $recentDevices,
            'polling_enabled' => (bool) $this->settings->get('polling_enabled', true),
            'placeholders' => [
                'cpu' => $latestMetrics?->avg_cpu !== null ? 'Average over last hour' : 'Will appear after first successful poll',
                'memory' => $latestMetrics?->avg_memory !== null ? 'Average over last hour' : 'Will appear after first successful poll',
                'temperature' => 'Vendor-specific; not all devices expose this',
                'bandwidth' => 'See device Overview charts after marking uplinks',
                'alerts' => 'From live poll threshold evaluation',
            ],
        ];
    }
}
