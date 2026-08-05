<?php

namespace Modules\Metrics\Console;

use App\Core\Enums\DeviceStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Devices\Models\Device;
use Modules\Metrics\Models\DeviceMetricRollup;
use Modules\Settings\Services\SnmpAgentClient;
use Throwable;

class RollupMetricsCommand extends Command
{
    protected $signature = 'metrics:rollup
                            {--device= : Limit to one Laravel device id}
                            {--keep-days=30 : Drop rollups older than this many days}';

    protected $description = 'Build compact 5m/1h metric rollups from snmp-agent hot samples (or skip when agent offline)';

    public function handle(SnmpAgentClient $agent): int
    {
        if (! $agent->configured()) {
            $this->warn('snmp-agent not configured — nothing to roll up (local installs keep raw Laravel device_metrics).');

            return self::SUCCESS;
        }

        $query = Device::query()->where('status', DeviceStatus::Active);
        if ($id = $this->option('device')) {
            $query->whereKey((int) $id);
        }

        $devices = $query->get();
        $written = 0;

        foreach ($devices as $device) {
            try {
                $written += $this->rollupDevice($agent, $device, '5m', 300);
                $written += $this->rollupDevice($agent, $device, '1h', 3600);
            } catch (Throwable $e) {
                $this->warn("Device {$device->id}: ".$e->getMessage());
            }
        }

        $keepDays = max(1, (int) $this->option('keep-days'));
        $deleted = DeviceMetricRollup::query()
            ->where('bucket_at', '<', now()->subDays($keepDays))
            ->delete();

        $this->info("Wrote {$written} rollup bucket(s); pruned {$deleted} old row(s).");

        return self::SUCCESS;
    }

    private function rollupDevice(SnmpAgentClient $agent, Device $device, string $period, int $bucketSeconds): int
    {
        $from = now()->subHours($period === '1h' ? 48 : 6);
        $rows = $agent->deviceMetrics(
            $device,
            $from->utc()->toIso8601String(),
            now()->utc()->toIso8601String(),
            5000,
        );

        if ($rows === []) {
            return 0;
        }

        /** @var array<string, list<array{cpu: ?float, memory: ?float, temperature: ?float}>> $buckets */
        $buckets = [];
        foreach ($rows as $row) {
            if (! isset($row['recorded_at'])) {
                continue;
            }
            $at = Carbon::parse((string) $row['recorded_at']);
            $bucketAt = $this->floorToBucket($at, $bucketSeconds)->toDateTimeString();
            $buckets[$bucketAt][] = [
                'cpu' => isset($row['cpu']) ? (float) $row['cpu'] : null,
                'memory' => isset($row['memory']) ? (float) $row['memory'] : null,
                'temperature' => isset($row['temperature']) ? (float) $row['temperature'] : null,
            ];
        }

        $count = 0;
        foreach ($buckets as $bucketAt => $samples) {
            $cpu = array_values(array_filter(array_column($samples, 'cpu'), static fn ($v) => $v !== null));
            $mem = array_values(array_filter(array_column($samples, 'memory'), static fn ($v) => $v !== null));
            $temp = array_values(array_filter(array_column($samples, 'temperature'), static fn ($v) => $v !== null));

            DeviceMetricRollup::query()->updateOrCreate(
                [
                    'device_id' => $device->id,
                    'period' => $period,
                    'bucket_at' => $bucketAt,
                ],
                [
                    'cpu_avg' => $cpu === [] ? null : round(array_sum($cpu) / count($cpu), 2),
                    'cpu_max' => $cpu === [] ? null : max($cpu),
                    'memory_avg' => $mem === [] ? null : round(array_sum($mem) / count($mem), 2),
                    'memory_max' => $mem === [] ? null : max($mem),
                    'temperature_avg' => $temp === [] ? null : round(array_sum($temp) / count($temp), 2),
                    'temperature_max' => $temp === [] ? null : max($temp),
                    'samples' => count($samples),
                ]
            );
            $count++;
        }

        return $count;
    }

    private function floorToBucket(Carbon $at, int $bucketSeconds): Carbon
    {
        $ts = $at->getTimestamp();
        $floored = intdiv($ts, $bucketSeconds) * $bucketSeconds;

        return Carbon::createFromTimestamp($floored, $at->timezone);
    }
}
