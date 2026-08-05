<?php

namespace Modules\SNMP\Services;

use Modules\Devices\Models\Device;
use Modules\Interfaces\Models\DeviceInterface;
use Throwable;

/**
 * Collects Huawei/MA580x PON + ONU style data when exposed over SNMP.
 * Falls back to classifying IF-MIB ports named PON/GPON when enterprise OIDs are unavailable.
 */
class HuaweiOltCollector
{
    public function __construct(
        private readonly SNMPService $snmp,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $interfaces
     * @return array{pon_ports: array<int, array<string, mixed>>, onus: array<int, array<string, mixed>>}
     */
    public function collect(Device $device, array $interfaces): array
    {
        $ponPorts = [];
        $onus = [];

        foreach ($interfaces as $iface) {
            $name = strtolower((string) $iface['name']);
            $desc = strtolower((string) ($iface['description'] ?? ''));
            if (! str_contains($name, 'pon') && ! str_contains($desc, 'pon') && ! str_contains($name, 'gpon')) {
                continue;
            }

            $ponPorts[(int) $iface['if_index']] = [
                'onu_online' => $iface['oper_status'] === 'up' ? 1 : 0,
                'onu_total' => $iface['oper_status'] === 'up' ? 1 : 0,
                'rx_power_dbm' => null,
                'tx_power_dbm' => null,
                'temperature' => null,
            ];
        }

        // Attempt common Huawei ONU serial/status tables (best-effort; silent on failure).
        try {
            $serials = $this->snmp->walk($device, '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.3');
            $statuses = $this->snmp->walk($device, '1.3.6.1.4.1.2011.6.128.1.1.2.46.1.15');

            $statusMap = [];
            foreach ($statuses as $statusOid) {
                $statusMap[$statusOid->oid] = strtolower((string) $statusOid->value);
            }

            foreach ($serials as $idx => $serialOid) {
                $serial = trim((string) $serialOid->value);
                if ($serial === '') {
                    continue;
                }

                $statusValue = 'unknown';
                foreach ($statusMap as $oid => $value) {
                    if (str_ends_with($oid, '.'.($idx + 1)) || str_contains($oid, $serialOid->oid)) {
                        $statusValue = $this->mapOnuStatus($value);
                        break;
                    }
                }

                // Heuristic: distribute onto first PON interface.
                $pon = DeviceInterface::query()
                    ->where('device_id', $device->id)
                    ->where('port_role', 'pon')
                    ->orderBy('if_index')
                    ->first();

                $onus[] = [
                    'device_interface_id' => $pon?->id,
                    'serial' => $serial,
                    'description' => $serial,
                    'pon_port' => $pon?->name,
                    'onu_id' => $idx + 1,
                    'status' => $statusValue,
                    'rx_power_dbm' => null,
                    'tx_power_dbm' => null,
                    'distance_m' => null,
                    'temperature' => null,
                    'customer' => null,
                ];
            }

            // Roll up ONU counts onto PON ports.
            if ($onus !== []) {
                $byPon = [];
                foreach ($onus as $onu) {
                    $key = $onu['pon_port'] ?? 'unknown';
                    $byPon[$key] ??= ['online' => 0, 'total' => 0];
                    $byPon[$key]['total']++;
                    if ($onu['status'] === 'online') {
                        $byPon[$key]['online']++;
                    }
                }

                foreach ($ponPorts as $ifIndex => $meta) {
                    $iface = collect($interfaces)->firstWhere('if_index', $ifIndex);
                    $name = $iface['name'] ?? null;
                    if ($name && isset($byPon[$name])) {
                        $ponPorts[$ifIndex]['onu_online'] = $byPon[$name]['online'];
                        $ponPorts[$ifIndex]['onu_total'] = $byPon[$name]['total'];
                    }
                }
            }
        } catch (Throwable) {
            // Enterprise OIDs unavailable — IF-MIB PON classification still applies.
        }

        return [
            'pon_ports' => $ponPorts,
            'onus' => $onus,
        ];
    }

    private function mapOnuStatus(string $value): string
    {
        if (in_array($value, ['1', 'up', 'online', 'active'], true)) {
            return 'online';
        }

        if (in_array($value, ['2', 'down', 'offline', 'los', 'dyinggasp'], true)) {
            return 'offline';
        }

        return 'unknown';
    }
}
