<?php

namespace Modules\SNMP\Services;

use FreeDSx\Snmp\Exception\ConnectionException;
use FreeDSx\Snmp\Exception\SnmpRequestException;
use FreeDSx\Snmp\Oid;
use FreeDSx\Snmp\SnmpClient;
use Illuminate\Support\Facades\Log;
use Modules\Devices\Models\Device;
use Modules\SNMP\Dto\SnmpConnectionResult;
use Modules\SNMP\Dto\SnmpOidResult;
use Modules\SNMP\Dto\SnmpSystemInfo;
use Throwable;

class SNMPService
{
    private const OID_SYS_DESCR = '1.3.6.1.2.1.1.1.0';

    private const OID_SYS_UPTIME = '1.3.6.1.2.1.1.3.0';

    private const OID_SYS_CONTACT = '1.3.6.1.2.1.1.4.0';

    private const OID_SYS_NAME = '1.3.6.1.2.1.1.5.0';

    private const OID_SYS_LOCATION = '1.3.6.1.2.1.1.6.0';

    private const OID_IF_NUMBER = '1.3.6.1.2.1.2.1.0';

    private const OID_IF_DESCR = '1.3.6.1.2.1.2.2.1.2';

    private const OID_IF_SPEED = '1.3.6.1.2.1.2.2.1.5';

    private const OID_IF_OPER_STATUS = '1.3.6.1.2.1.2.2.1.8';

    private const OID_IF_IN_OCTETS = '1.3.6.1.2.1.2.2.1.10';

    private const OID_IF_IN_ERRORS = '1.3.6.1.2.1.2.2.1.14';

    private const OID_IF_OUT_OCTETS = '1.3.6.1.2.1.2.2.1.16';

    private const OID_IF_HC_IN_OCTETS = '1.3.6.1.2.1.31.1.1.1.6';

    private const OID_IF_HC_OUT_OCTETS = '1.3.6.1.2.1.31.1.1.1.10';

    private const OID_IF_ALIAS = '1.3.6.1.2.1.31.1.1.1.18';

    private const OID_HR_PROCESSOR_LOAD = '1.3.6.1.2.1.25.3.3.1.2';

    private const OID_HR_STORAGE_TYPE = '1.3.6.1.2.1.25.2.3.1.2';

    private const OID_HR_STORAGE_USED = '1.3.6.1.2.1.25.2.3.1.6';

    private const OID_HR_STORAGE_SIZE = '1.3.6.1.2.1.25.2.3.1.5';

    public function __construct(
        private readonly SnmpClientFactory $clients,
    ) {}

    public function testConnection(Device $device): SnmpConnectionResult
    {
        try {
            $client = $this->clients->make($device);
            $system = $this->readSystemInformation($device);

            $interfacesCount = 0;
            try {
                $interfacesCount = (int) ($client->getValue(self::OID_IF_NUMBER) ?? 0);
            } catch (Throwable) {
                // Some devices omit ifNumber; still treat as connected.
            }

            return new SnmpConnectionResult(
                connected: true,
                message: 'Connected',
                system: $system,
                interfacesCount: $interfacesCount,
            );
        } catch (Throwable $e) {
            $this->logError($device, 'testConnection', $e);

            return new SnmpConnectionResult(
                connected: false,
                message: $this->friendlyMessage($e),
            );
        }
    }

    public function get(Device $device, string $oid): SnmpOidResult
    {
        $client = $this->clients->make($device);

        try {
            $result = $client->getOid($this->normalizeOid($oid));

            if (! $result) {
                return new SnmpOidResult($oid, 'Null', null);
            }

            return $this->mapOid($result);
        } catch (Throwable $e) {
            $this->logError($device, 'get', $e, ['oid' => $oid]);
            throw $e;
        }
    }

    /**
     * @return array<int, SnmpOidResult>
     */
    public function walk(Device $device, string $oid): array
    {
        $client = $this->clients->make($device);
        $results = [];

        try {
            $walk = $client->walk($this->normalizeOid($oid));

            while ($walk->hasOids()) {
                $results[] = $this->mapOid($walk->next());
            }

            return $results;
        } catch (Throwable $e) {
            $this->logError($device, 'walk', $e, ['oid' => $oid]);
            throw $e;
        }
    }

    public function readSystemInformation(Device $device): SnmpSystemInfo
    {
        $client = $this->clients->make($device);

        try {
            return new SnmpSystemInfo(
                hostname: $client->getValue(self::OID_SYS_NAME),
                description: $client->getValue(self::OID_SYS_DESCR),
                uptime: $client->getValue(self::OID_SYS_UPTIME),
                location: $client->getValue(self::OID_SYS_LOCATION),
                contact: $client->getValue(self::OID_SYS_CONTACT),
            );
        } catch (Throwable $e) {
            $this->logError($device, 'readSystemInformation', $e);
            throw $e;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function readInterfaceTable(Device $device): array
    {
        $client = $this->clients->make($device);

        try {
            $names = $this->walkColumn($client, self::OID_IF_DESCR);
            $speeds = $this->walkColumn($client, self::OID_IF_SPEED);
            $statuses = $this->walkColumn($client, self::OID_IF_OPER_STATUS);
            $inErrors = $this->walkColumn($client, self::OID_IF_IN_ERRORS);
            $aliases = $this->walkColumn($client, self::OID_IF_ALIAS);

            $rx = $this->walkColumn($client, self::OID_IF_HC_IN_OCTETS);
            $tx = $this->walkColumn($client, self::OID_IF_HC_OUT_OCTETS);

            if ($rx === []) {
                $rx = $this->walkColumn($client, self::OID_IF_IN_OCTETS);
            }

            if ($tx === []) {
                $tx = $this->walkColumn($client, self::OID_IF_OUT_OCTETS);
            }

            $indexes = array_unique(array_merge(
                array_keys($names),
                array_keys($statuses),
                array_keys($speeds),
            ));
            sort($indexes, SORT_NUMERIC);

            $interfaces = [];

            foreach ($indexes as $index) {
                $speed = isset($speeds[$index]) ? (int) $speeds[$index] : null;
                $rxBytes = isset($rx[$index]) ? (string) $rx[$index] : '0';
                $txBytes = isset($tx[$index]) ? (string) $tx[$index] : '0';
                $oper = isset($statuses[$index]) ? (int) $statuses[$index] : null;

                $interfaces[] = [
                    'if_index' => (int) $index,
                    'name' => (string) ($names[$index] ?? 'if'.$index),
                    'description' => (string) ($aliases[$index] ?? $names[$index] ?? ''),
                    'oper_status' => $this->mapOperStatus($oper),
                    'speed' => $speed,
                    'rx_bytes' => $rxBytes,
                    'tx_bytes' => $txBytes,
                    'errors' => isset($inErrors[$index]) ? (int) $inErrors[$index] : 0,
                    'utilization' => null,
                ];
            }

            return $interfaces;
        } catch (Throwable $e) {
            $this->logError($device, 'readInterfaceTable', $e);
            throw $e;
        }
    }

    /**
     * @return array{cpu: ?float, memory: ?float, temperature: ?float, rx_bytes: string, tx_bytes: string, uptime: ?string}
     */
    public function readTrafficCounters(Device $device): array
    {
        $client = $this->clients->make($device);

        try {
            $system = $this->readSystemInformation($device);
            $interfaces = $this->readInterfaceTable($device);

            $rxTotal = '0';
            $txTotal = '0';

            foreach ($interfaces as $interface) {
                $rxTotal = $this->bcAdd($rxTotal, (string) $interface['rx_bytes']);
                $txTotal = $this->bcAdd($txTotal, (string) $interface['tx_bytes']);
            }

            return [
                'cpu' => $this->readCpuPercent($client),
                'memory' => $this->readMemoryPercent($client),
                'temperature' => null,
                'rx_bytes' => $rxTotal,
                'tx_bytes' => $txTotal,
                'uptime' => $system->uptime,
                'hostname' => $system->hostname,
                'location' => $system->location,
                'description' => $system->description,
                'interfaces' => $interfaces,
            ];
        } catch (Throwable $e) {
            $this->logError($device, 'readTrafficCounters', $e);
            throw $e;
        }
    }

    private function mapOid(Oid $oid): SnmpOidResult
    {
        $value = $oid->getValue();

        return new SnmpOidResult(
            oid: $oid->getOid(),
            type: $value ? class_basename($value) : 'Null',
            value: $value !== null ? (string) $value : null,
        );
    }

    /**
     * @return array<int|string, string>
     */
    private function walkColumn(SnmpClient $client, string $baseOid): array
    {
        $rows = [];
        $walk = $client->walk($baseOid);
        $walk->maxRepetitions(10);

        while ($walk->hasOids()) {
            $oid = $walk->next();
            $index = $this->extractIndex($baseOid, $oid->getOid());

            if ($index !== null) {
                $rows[$index] = (string) $oid->getValue();
            }
        }

        return $rows;
    }

    private function extractIndex(string $baseOid, string $fullOid): ?string
    {
        $base = trim($baseOid, '.');
        $full = trim($fullOid, '.');

        if (! str_starts_with($full, $base.'.')) {
            return null;
        }

        return substr($full, strlen($base) + 1);
    }

    private function readCpuPercent(SnmpClient $client): ?float
    {
        try {
            $loads = array_map('floatval', array_values($this->walkColumn($client, self::OID_HR_PROCESSOR_LOAD)));

            if ($loads === []) {
                return null;
            }

            return round(array_sum($loads) / count($loads), 2);
        } catch (Throwable) {
            return null;
        }
    }

    private function readMemoryPercent(SnmpClient $client): ?float
    {
        try {
            $types = $this->walkColumn($client, self::OID_HR_STORAGE_TYPE);
            $used = $this->walkColumn($client, self::OID_HR_STORAGE_USED);
            $size = $this->walkColumn($client, self::OID_HR_STORAGE_SIZE);

            $ramType = '1.3.6.1.2.1.25.2.1.2';
            $candidates = [];

            foreach ($types as $index => $type) {
                if (str_contains((string) $type, $ramType) || str_ends_with((string) $type, '.2')) {
                    $total = (float) ($size[$index] ?? 0);
                    $usedValue = (float) ($used[$index] ?? 0);

                    if ($total > 0) {
                        $candidates[] = ($usedValue / $total) * 100;
                    }
                }
            }

            if ($candidates === []) {
                return null;
            }

            return round(max($candidates), 2);
        } catch (Throwable) {
            return null;
        }
    }

    private function mapOperStatus(?int $status): string
    {
        return match ($status) {
            1 => 'up',
            2 => 'down',
            3 => 'testing',
            4 => 'unknown',
            5 => 'dormant',
            6 => 'notPresent',
            7 => 'lowerLayerDown',
            default => 'unknown',
        };
    }

    private function oidString(mixed $oid): ?string
    {
        if ($oid === null) {
            return null;
        }

        if ($oid instanceof Oid) {
            $value = $oid->getValue();

            return $value !== null ? (string) $value : null;
        }

        return (string) $oid;
    }

    private function normalizeOid(string $oid): string
    {
        return ltrim(trim($oid), '.');
    }

    private function bcAdd(string $left, string $right): string
    {
        if (function_exists('bcadd')) {
            return bcadd($left, $right, 0);
        }

        return (string) ((int) $left + (int) $right);
    }

    private function friendlyMessage(Throwable $e): string
    {
        if ($e instanceof ConnectionException) {
            return 'Connection Failed: device unreachable or timed out.';
        }

        if ($e instanceof SnmpRequestException) {
            return 'Connection Failed: '.$e->getMessage();
        }

        return 'Connection Failed: '.$e->getMessage();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logError(Device $device, string $operation, Throwable $e, array $context = []): void
    {
        Log::channel('snmp')->error("SNMP {$operation} failed for device {$device->id}", array_merge([
            'device_id' => $device->id,
            'ip' => $device->ip_address,
            'port' => $device->port,
            'error' => $e->getMessage(),
        ], $context));
    }
}
