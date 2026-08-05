<?php

namespace Modules\Dashboard\Services;

use Modules\Devices\Repositories\Contracts\DeviceRepositoryInterface;
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

        $recentDevices = $this->devices
            ->search([], 5);

        return [
            'stats' => [
                'total_devices' => $deviceStats['total'] ?? 0,
                'active_devices' => $deviceStats['active'] ?? 0,
                'online' => $deviceStats['online'] ?? 0,
                'offline' => $deviceStats['offline'] ?? 0,
                'unknown' => $deviceStats['unknown'] ?? 0,
                'open_alerts' => 0,
                'avg_cpu' => null,
                'avg_memory' => null,
                'avg_temperature' => null,
                'bandwidth_mbps' => null,
                'last_poll' => null,
            ],
            'recent_devices' => $recentDevices,
            'polling_enabled' => (bool) $this->settings->get('polling_enabled', true),
            'placeholders' => [
                'cpu' => 'Available after Phase 2 polling',
                'memory' => 'Available after Phase 2 polling',
                'temperature' => 'Available after Phase 2 polling',
                'bandwidth' => 'Available after Phase 2 metrics',
                'alerts' => 'Available after Phase 2 alert engine',
            ],
        ];
    }
}
