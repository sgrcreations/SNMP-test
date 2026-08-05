<?php

namespace Modules\SNMP\Services;

use Modules\Devices\Models\Device;
use Modules\SNMP\Dto\SnmpConnectionResult;
use Modules\SNMP\Dto\SnmpOidResult;
use Modules\SNMP\Dto\SnmpSystemInfo;
use RuntimeException;

/**
 * SNMPService — Phase 2 entry point.
 *
 * This service is structured now so Device Test Connection / OID Explorer
 * can plug in without redesigning the Devices module.
 */
class SNMPService
{
    public function testConnection(Device $device): SnmpConnectionResult
    {
        throw new RuntimeException('SNMP live polling is implemented in Phase 2.');
    }

    public function get(Device $device, string $oid): SnmpOidResult
    {
        throw new RuntimeException('SNMP GET is implemented in Phase 2.');
    }

    /**
     * @return array<int, SnmpOidResult>
     */
    public function walk(Device $device, string $oid): array
    {
        throw new RuntimeException('SNMP WALK is implemented in Phase 2.');
    }

    public function readSystemInformation(Device $device): SnmpSystemInfo
    {
        throw new RuntimeException('System information polling is implemented in Phase 2.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function readInterfaceTable(Device $device): array
    {
        throw new RuntimeException('Interface table polling is implemented in Phase 2.');
    }

    /**
     * @return array<string, mixed>
     */
    public function readTrafficCounters(Device $device): array
    {
        throw new RuntimeException('Traffic counter polling is implemented in Phase 2.');
    }
}
