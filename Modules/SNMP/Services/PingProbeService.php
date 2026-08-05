<?php

namespace Modules\SNMP\Services;

use Modules\Devices\Models\Device;

/**
 * ICMP probe using the OS ping binary (no mock values).
 */
class PingProbeService
{
    /**
     * @return array{
     *     latency_ms: ?float,
     *     jitter_ms: ?float,
     *     packet_loss_pct: float,
     *     packets_sent: int,
     *     packets_received: int
     * }
     */
    public function probe(Device $device, int $count = 4): array
    {
        $count = max(2, min(10, $count));
        $ip = $device->ip_address;

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return $this->empty(100.0, $count, 0);
        }

        $command = $this->buildCommand($ip, $count);
        $output = [];
        $exit = 1;
        exec($command, $output, $exit);
        $text = implode("\n", $output);

        $rtt = $this->extractRtts($text);

        if ($rtt === []) {
            // Some Windows locales omit per-line times; fall back to avg from summary.
            $avg = $this->extractAverage($text);
            $received = $exit === 0 && $avg !== null ? $count : 0;
            $loss = $received === 0 ? 100.0 : 0.0;

            return [
                'latency_ms' => $avg,
                'jitter_ms' => null,
                'packet_loss_pct' => $loss,
                'packets_sent' => $count,
                'packets_received' => $received,
            ];
        }

        $received = count($rtt);
        $loss = round((($count - $received) / $count) * 100, 2);
        $avg = array_sum($rtt) / $received;
        $jitter = null;

        if ($received > 1) {
            $diffs = [];
            for ($i = 1; $i < $received; $i++) {
                $diffs[] = abs($rtt[$i] - $rtt[$i - 1]);
            }
            $jitter = array_sum($diffs) / count($diffs);
        }

        return [
            'latency_ms' => round($avg, 2),
            'jitter_ms' => $jitter !== null ? round($jitter, 2) : null,
            'packet_loss_pct' => $loss,
            'packets_sent' => $count,
            'packets_received' => $received,
        ];
    }

    private function buildCommand(string $ip, int $count): string
    {
        $safeIp = escapeshellarg($ip);

        if (PHP_OS_FAMILY === 'Windows') {
            return "ping -n {$count} -w 1000 {$safeIp} 2>&1";
        }

        // macOS uses -W in milliseconds; Linux often uses seconds for -W.
        if (PHP_OS_FAMILY === 'Darwin') {
            return "ping -c {$count} -W 1000 {$safeIp} 2>&1";
        }

        return "ping -c {$count} -W 1 {$safeIp} 2>&1";
    }

    /**
     * @return array<int, float>
     */
    private function extractRtts(string $text): array
    {
        preg_match_all('/time[=<]\s*([0-9.]+)\s*ms/i', $text, $matches);

        return array_map(static fn (string $v): float => (float) $v, $matches[1] ?? []);
    }

    private function extractAverage(string $text): ?float
    {
        if (preg_match('/=\s*[0-9.]+\/([0-9.]+)\/[0-9.]+/i', $text, $m)) {
            return round((float) $m[1], 2);
        }

        if (preg_match('/Average\s*=\s*([0-9.]+)\s*ms/i', $text, $m)) {
            return round((float) $m[1], 2);
        }

        return null;
    }

    /**
     * @return array{latency_ms: ?float, jitter_ms: ?float, packet_loss_pct: float, packets_sent: int, packets_received: int}
     */
    private function empty(float $loss, int $sent, int $received): array
    {
        return [
            'latency_ms' => null,
            'jitter_ms' => null,
            'packet_loss_pct' => $loss,
            'packets_sent' => $sent,
            'packets_received' => $received,
        ];
    }
}
