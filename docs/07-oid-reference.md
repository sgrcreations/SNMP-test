# 07 — OID Reference

Common baseline OIDs used by Phase 2 polling.

## System Group (SNMPv2-MIB)

| OID | Object | Use |
| --- | --- | --- |
| `.1.3.6.1.2.1.1.1.0` | `sysDescr` | System description |
| `.1.3.6.1.2.1.1.3.0` | `sysUpTime` | Uptime |
| `.1.3.6.1.2.1.1.4.0` | `sysContact` | Contact |
| `.1.3.6.1.2.1.1.5.0` | `sysName` | Hostname |
| `.1.3.6.1.2.1.1.6.0` | `sysLocation` | Location |

## Interfaces (IF-MIB)

| OID | Object | Use |
| --- | --- | --- |
| `.1.3.6.1.2.1.2.2.1.2` | `ifDescr` | Interface name |
| `.1.3.6.1.2.1.2.2.1.5` | `ifSpeed` | Speed |
| `.1.3.6.1.2.1.2.2.1.8` | `ifOperStatus` | Oper status |
| `.1.3.6.1.2.1.2.2.1.10` | `ifInOctets` | RX bytes |
| `.1.3.6.1.2.1.2.2.1.16` | `ifOutOctets` | TX bytes |
| `.1.3.6.1.2.1.2.2.1.14` | `ifInErrors` | RX errors |
| `.1.3.6.1.2.1.31.1.1.1.6` | `ifHCInOctets` | 64-bit RX |
| `.1.3.6.1.2.1.31.1.1.1.10` | `ifHCOutOctets` | 64-bit TX |

## Host Resources / Vendor CPU/Mem

Vendor-specific OIDs vary. Phase 2 will register mappers per `DeviceVendor` enum:

- Huawei
- MikroTik
- Cisco
- Juniper
- Fortinet
- Aruba
- Ubiquiti
- Generic SNMP fallback
