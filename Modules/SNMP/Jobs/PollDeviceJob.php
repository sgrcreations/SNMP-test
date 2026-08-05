<?php

namespace Modules\SNMP\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Devices\Models\Device;
use Modules\SNMP\Services\DevicePollService;
use Throwable;

class PollDeviceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public readonly int $deviceId,
    ) {}

    public function handle(DevicePollService $poller): void
    {
        $device = Device::query()->find($this->deviceId);

        if (! $device || ! $device->isPollable()) {
            return;
        }

        $result = $poller->poll($device);

        Log::channel('snmp')->info('PollDeviceJob completed', [
            'device_id' => $device->id,
            'success' => $result['success'],
            'message' => $result['message'],
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('snmp')->error('PollDeviceJob failed', [
            'device_id' => $this->deviceId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
