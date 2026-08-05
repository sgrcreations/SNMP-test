<?php

namespace Modules\SNMP\Console;

use App\Core\Enums\DeviceStatus;
use Illuminate\Console\Command;
use Modules\Devices\Models\Device;
use Modules\Settings\Services\SettingService;
use Modules\SNMP\Jobs\PollDeviceJob;
use Modules\SNMP\Services\DevicePollService;

class PollDevicesCommand extends Command
{
    protected $signature = 'devices:poll
                            {--sync : Run polling inline instead of queueing jobs}
                            {--device= : Poll a single device ID}';

    protected $description = 'Poll active SNMP devices that are due for collection';

    public function handle(SettingService $settings, DevicePollService $poller): int
    {
        if (! (bool) $settings->get('polling_enabled', true)) {
            $this->warn('Polling is disabled in Settings.');

            return self::SUCCESS;
        }

        $query = Device::query()->where('status', DeviceStatus::Active);

        if ($deviceId = $this->option('device')) {
            $query->whereKey((int) $deviceId);
        }

        $devices = $query->get()->filter(function (Device $device): bool {
            if ($device->last_polled_at === null) {
                return true;
            }

            return $device->last_polled_at->copy()
                ->addSeconds(max(30, (int) $device->polling_interval))
                ->lte(now());
        });

        if ($devices->isEmpty()) {
            $this->info('No devices are due for polling.');

            return self::SUCCESS;
        }

        $this->info('Polling '.$devices->count().' device(s)...');

        foreach ($devices as $device) {
            if ($this->option('sync')) {
                $result = $poller->poll($device);
                $this->line(sprintf(
                    '[%s] %s → %s',
                    $device->id,
                    $device->displayEndpoint(),
                    $result['success'] ? 'OK' : 'FAIL: '.$result['message']
                ));

                continue;
            }

            PollDeviceJob::dispatch($device->id);
            $this->line(sprintf('[%s] queued %s', $device->id, $device->displayEndpoint()));
        }

        return self::SUCCESS;
    }
}
