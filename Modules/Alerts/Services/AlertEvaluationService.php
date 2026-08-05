<?php

namespace Modules\Alerts\Services;

use Modules\Alerts\Models\Alert;
use Modules\Devices\Models\Device;
use Modules\Settings\Services\SettingService;

class AlertEvaluationService
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    public function evaluate(Device $device): void
    {
        $cpuThreshold = (int) $this->settings->get('cpu_threshold', 85);
        $memThreshold = (int) $this->settings->get('memory_threshold', 90);
        $tempThreshold = (int) $this->settings->get('temperature_threshold', 70);
        $bwThreshold = (int) $this->settings->get('bandwidth_threshold', 80);

        if ($device->last_cpu !== null && $device->last_cpu >= $cpuThreshold) {
            $this->open($device, 'cpu_high', 'warning', 'CPU high', "CPU {$device->last_cpu}% >= {$cpuThreshold}%");
        } else {
            $this->resolve($device, 'cpu_high');
        }

        if ($device->last_memory !== null && $device->last_memory >= $memThreshold) {
            $this->open($device, 'memory_high', 'warning', 'Memory high', "Memory {$device->last_memory}% >= {$memThreshold}%");
        } else {
            $this->resolve($device, 'memory_high');
        }

        if ($device->last_temperature !== null && $device->last_temperature >= $tempThreshold) {
            $this->open($device, 'temperature_high', 'critical', 'Temperature high', "Temperature {$device->last_temperature}°C >= {$tempThreshold}°C");
        } else {
            $this->resolve($device, 'temperature_high');
        }

        $device->loadMissing('interfaces');
        foreach ($device->interfaces as $interface) {
            if ($interface->oper_status === 'down') {
                Alert::query()->firstOrCreate(
                    [
                        'device_id' => $device->id,
                        'device_interface_id' => $interface->id,
                        'type' => 'interface_down',
                        'status' => 'open',
                    ],
                    [
                        'severity' => 'warning',
                        'title' => "{$interface->name} down",
                        'message' => "Interface {$interface->name} is operationally down.",
                        'raised_at' => now(),
                    ]
                );
            } else {
                Alert::query()
                    ->where('device_id', $device->id)
                    ->where('device_interface_id', $interface->id)
                    ->where('type', 'interface_down')
                    ->where('status', 'open')
                    ->update([
                        'status' => 'resolved',
                        'resolved_at' => now(),
                    ]);
            }

            if ($interface->utilization !== null && $interface->utilization >= $bwThreshold) {
                Alert::query()->firstOrCreate(
                    [
                        'device_id' => $device->id,
                        'device_interface_id' => $interface->id,
                        'type' => 'high_bandwidth',
                        'status' => 'open',
                    ],
                    [
                        'severity' => 'warning',
                        'title' => "{$interface->name} high utilization",
                        'message' => "Utilization {$interface->utilization}% >= {$bwThreshold}%",
                        'raised_at' => now(),
                    ]
                );
            }
        }

        // Resolve offline alert on successful poll.
        $this->resolve($device, 'device_offline');
    }

    private function open(Device $device, string $type, string $severity, string $title, string $message): void
    {
        Alert::query()->firstOrCreate(
            [
                'device_id' => $device->id,
                'type' => $type,
                'status' => 'open',
            ],
            [
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'raised_at' => now(),
            ]
        );
    }

    private function resolve(Device $device, string $type): void
    {
        Alert::query()
            ->where('device_id', $device->id)
            ->where('type', $type)
            ->where('status', 'open')
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);
    }
}
