<?php

namespace Modules\SNMP\Services;

use App\Core\Enums\DeviceStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Devices\Models\Device;
use Modules\Interfaces\Models\DeviceInterface;
use Modules\Metrics\Models\DeviceMetric;
use Modules\Metrics\Models\InterfaceMetric;
use Throwable;

class DevicePollService
{
    public function __construct(
        private readonly SNMPService $snmp,
    ) {}

    /**
     * @return array{success: bool, message: string, metrics?: array<string, mixed>}
     */
    public function poll(Device $device): array
    {
        $startedAt = now();

        try {
            $snapshot = $this->snmp->readTrafficCounters($device);

            DB::transaction(function () use ($device, $snapshot, $startedAt): void {
                DeviceMetric::query()->create([
                    'device_id' => $device->id,
                    'cpu' => $snapshot['cpu'],
                    'memory' => $snapshot['memory'],
                    'temperature' => $snapshot['temperature'],
                    'rx_bytes' => $snapshot['rx_bytes'],
                    'tx_bytes' => $snapshot['tx_bytes'],
                    'uptime' => $snapshot['uptime'],
                    'recorded_at' => $startedAt,
                ]);

                foreach ($snapshot['interfaces'] as $row) {
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
                            'utilization' => $row['utilization'],
                            'last_polled_at' => $startedAt,
                        ]
                    );

                    InterfaceMetric::query()->create([
                        'device_id' => $device->id,
                        'device_interface_id' => $interface->id,
                        'rx_bytes' => $row['rx_bytes'],
                        'tx_bytes' => $row['tx_bytes'],
                        'errors' => $row['errors'],
                        'utilization' => $row['utilization'],
                        'recorded_at' => $startedAt,
                    ]);
                }

                $device->forceFill([
                    'hostname' => $snapshot['hostname'] ?: $device->hostname,
                    'location' => $snapshot['location'] ?: $device->location,
                    'reachability' => DeviceStatus::Online,
                    'last_polled_at' => $startedAt,
                    'last_seen_at' => $startedAt,
                ])->save();
            });

            return [
                'success' => true,
                'message' => 'Polled successfully',
                'metrics' => [
                    'cpu' => $snapshot['cpu'],
                    'memory' => $snapshot['memory'],
                    'interfaces' => count($snapshot['interfaces']),
                ],
            ];
        } catch (Throwable $e) {
            Log::channel('snmp')->error('Device poll failed', [
                'device_id' => $device->id,
                'ip' => $device->ip_address,
                'error' => $e->getMessage(),
            ]);

            $device->forceFill([
                'reachability' => DeviceStatus::Offline,
                'last_polled_at' => $startedAt,
            ])->save();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
