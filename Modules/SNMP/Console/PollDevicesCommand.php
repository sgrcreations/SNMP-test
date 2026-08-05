<?php

namespace Modules\SNMP\Console;

use App\Core\Enums\DeviceStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Devices\Models\Device;
use Modules\Settings\Services\SettingService;
use Modules\Settings\Services\SnmpAgentClient;
use Modules\SNMP\Jobs\PollDeviceJob;
use Modules\SNMP\Services\DevicePollService;
use Throwable;

class PollDevicesCommand extends Command
{
    protected $signature = 'devices:poll
                            {--sync : Run polling inline instead of queueing jobs}
                            {--device= : Poll a single device ID}
                            {--force-poll : Even when snmp-agent is configured, run full Laravel poll jobs}';

    protected $description = 'Poll active SNMP devices that are due (or reconcile status from snmp-agent)';

    public function handle(
        SettingService $settings,
        DevicePollService $poller,
        SnmpAgentClient $agent,
    ): int {
        if (! (bool) $settings->get('polling_enabled', true)) {
            $this->warn('Polling is disabled in Settings.');

            return self::SUCCESS;
        }

        // Agent owns the SNMP cadence + hot metrics. Laravel only refreshes status fields.
        if ($agent->configured() && ! $this->option('force-poll')) {
            return $this->reconcileFromAgent($agent);
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

    private function reconcileFromAgent(SnmpAgentClient $agent): int
    {
        $this->info('snmp-agent configured — reconciling device status (no Laravel metric history writes).');

        try {
            $items = $agent->listDevices();
        } catch (Throwable $e) {
            $this->error('Agent reconcile failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $updated = 0;
        $filterId = $this->option('device') ? (int) $this->option('device') : null;

        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            $externalId = isset($row['external_id']) ? (int) $row['external_id'] : 0;
            if ($externalId < 1) {
                continue;
            }
            if ($filterId !== null && $externalId !== $filterId) {
                continue;
            }

            $device = Device::query()->find($externalId);
            if (! $device) {
                continue;
            }

            $reach = (string) ($row['reachability'] ?? 'unknown');
            $lastPolled = isset($row['last_polled_at']) ? Carbon::parse((string) $row['last_polled_at']) : null;
            $lastSeen = isset($row['last_seen_at']) ? Carbon::parse((string) $row['last_seen_at']) : null;

            $device->forceFill([
                'reachability' => in_array($reach, ['online', 'offline'], true) ? $reach : $device->reachability,
                'last_cpu' => array_key_exists('last_cpu', $row) ? $row['last_cpu'] : $device->last_cpu,
                'last_memory' => array_key_exists('last_memory', $row) ? $row['last_memory'] : $device->last_memory,
                'last_temperature' => array_key_exists('last_temperature', $row) ? $row['last_temperature'] : $device->last_temperature,
                'interface_count' => (int) ($row['interface_count'] ?? $device->interface_count),
                'last_polled_at' => $lastPolled ?? $device->last_polled_at,
                'last_seen_at' => $lastSeen ?? $device->last_seen_at,
            ])->save();

            $updated++;
            $this->line(sprintf('[%s] %s → %s', $device->id, $device->displayEndpoint(), $reach ?: 'unknown'));
        }

        $this->info("Reconciled {$updated} device(s) from snmp-agent.");

        return self::SUCCESS;
    }
}
