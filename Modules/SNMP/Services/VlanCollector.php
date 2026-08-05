<?php

namespace Modules\SNMP\Services;

use Modules\Devices\Models\Device;
use Throwable;

/**
 * Collects VLANs from Q-BRIDGE-MIB / IF-MIB / subinterfaces (real SNMP only).
 */
class VlanCollector
{
    private const OID_DOT1Q_VLAN_STATIC_NAME = '1.3.6.1.2.1.17.7.1.4.3.1.1';

    private const OID_DOT1Q_VLAN_CURRENT_EGRESS = '1.3.6.1.2.1.17.7.1.4.2.1.4';

    public function __construct(
        private readonly SNMPService $snmp,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $interfaces
     * @return array<int, array{vlan_id: int, name: ?string, status: string, member_ports: int}>
     */
    public function collect(Device $device, array $interfaces = []): array
    {
        $vlans = $this->fromQBridge($device);
        $fromIfaces = $this->fromInterfaces($interfaces);

        foreach ($fromIfaces as $vlanId => $row) {
            if (! isset($vlans[$vlanId])) {
                $vlans[$vlanId] = $row;
                continue;
            }

            if (empty($vlans[$vlanId]['name']) || str_starts_with((string) $vlans[$vlanId]['name'], 'VLAN ')) {
                $vlans[$vlanId]['name'] = $row['name'];
            }

            $vlans[$vlanId]['member_ports'] = max(
                (int) $vlans[$vlanId]['member_ports'],
                (int) $row['member_ports']
            );

            if ($row['status'] === 'active') {
                $vlans[$vlanId]['status'] = 'active';
            }
        }

        ksort($vlans);

        return array_values($vlans);
    }

    /**
     * @return array<int, array{vlan_id: int, name: ?string, status: string, member_ports: int}>
     */
    private function fromQBridge(Device $device): array
    {
        $vlans = [];

        try {
            $names = $this->snmp->walk($device, self::OID_DOT1Q_VLAN_STATIC_NAME);
            foreach ($names as $row) {
                $vlanId = $this->trailingIndex((string) $row->oid);
                if ($vlanId === null || $vlanId < 1 || $vlanId > 4094) {
                    continue;
                }
                $vlans[$vlanId] = [
                    'vlan_id' => $vlanId,
                    'name' => trim((string) $row->value) ?: ('VLAN '.$vlanId),
                    'status' => 'active',
                    'member_ports' => 0,
                ];
            }
        } catch (Throwable) {
            // Device may not expose Q-BRIDGE.
        }

        try {
            $egress = $this->snmp->walk($device, self::OID_DOT1Q_VLAN_CURRENT_EGRESS);
            foreach ($egress as $row) {
                $vlanId = $this->trailingIndex((string) $row->oid);
                if ($vlanId === null || $vlanId < 1 || $vlanId > 4094) {
                    continue;
                }

                $vlans[$vlanId] ??= [
                    'vlan_id' => $vlanId,
                    'name' => 'VLAN '.$vlanId,
                    'status' => 'active',
                    'member_ports' => 0,
                ];

                $bits = $this->countPortsInBitmask((string) $row->value);
                $vlans[$vlanId]['member_ports'] = max($vlans[$vlanId]['member_ports'], $bits);
            }
        } catch (Throwable) {
            // optional
        }

        return $vlans;
    }

    /**
     * @param  array<int, array<string, mixed>>  $interfaces
     * @return array<int, array{vlan_id: int, name: ?string, status: string, member_ports: int}>
     */
    private function fromInterfaces(array $interfaces): array
    {
        $vlans = [];

        foreach ($interfaces as $iface) {
            $name = (string) ($iface['name'] ?? '');
            $description = (string) ($iface['description'] ?? '');
            $hay = $name.' '.$description;
            $vlanId = null;
            $label = null;

            // Huawei / Cisco subinterfaces: GigabitEthernet0/2/1.1010
            if (preg_match('/\.(\d{1,4})$/', $name, $m)) {
                $vlanId = (int) $m[1];
                $label = trim($description) !== '' ? trim($description) : ('VLAN '.$vlanId);
            }

            // Explicit VLAN naming in descr/name
            if ($vlanId === null && preg_match('/\bvlan[_\s-]*(\d{1,4})\b/i', $hay, $m)) {
                $vlanId = (int) $m[1];
                $label = trim($description) !== '' ? trim($description) : ('VLAN '.$vlanId);
            }

            // Vlanif1010 / VLANIF20
            if ($vlanId === null && preg_match('/\bvlanif(\d{1,4})\b/i', $hay, $m)) {
                $vlanId = (int) $m[1];
                $label = trim($description) !== '' ? trim($description) : $name;
            }

            if ($vlanId === null || $vlanId < 1 || $vlanId > 4094) {
                continue;
            }

            $vlans[$vlanId] ??= [
                'vlan_id' => $vlanId,
                'name' => $label ?: ('VLAN '.$vlanId),
                'status' => ($iface['oper_status'] ?? '') === 'up' ? 'active' : 'inactive',
                'member_ports' => 0,
            ];

            if (($iface['oper_status'] ?? '') === 'up') {
                $vlans[$vlanId]['status'] = 'active';
            }

            if (
                (! empty($label) && str_starts_with((string) $vlans[$vlanId]['name'], 'VLAN '))
                || (empty($vlans[$vlanId]['name']) && $label)
            ) {
                $vlans[$vlanId]['name'] = $label;
            }

            $vlans[$vlanId]['member_ports']++;
        }

        return $vlans;
    }

    private function trailingIndex(string $oid): ?int
    {
        if (preg_match('/\.(\d+)$/', $oid, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function countPortsInBitmask(string $value): int
    {
        $hex = preg_replace('/[^0-9a-fA-F]/', '', $value) ?? '';
        if ($hex === '') {
            return 0;
        }

        $bin = '';
        foreach (str_split($hex, 2) as $byte) {
            if (strlen($byte) === 2) {
                $bin .= str_pad(decbin(hexdec($byte)), 8, '0', STR_PAD_LEFT);
            }
        }

        return substr_count($bin, '1');
    }
}
