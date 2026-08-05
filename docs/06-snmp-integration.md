# 06 — SNMP Integration

## Service Contract

`Modules\SNMP\Services\SNMPService` is the single entry point for SNMP I/O.

Methods:

- `testConnection(Device): SnmpConnectionResult`
- `get(Device, oid): SnmpOidResult`
- `walk(Device, oid): SnmpOidResult[]`
- `readSystemInformation(Device): SnmpSystemInfo`
- `readInterfaceTable(Device): array`
- `readTrafficCounters(Device): array`

DTOs live in `Modules\SNMP\Dto`.

## Supported Versions (Device Model)

### SNMP v2c
- Community string (encrypted)

### SNMP v3
- Username
- Auth protocol/password (encrypted)
- Privacy protocol/password (encrypted)

## Phase 1 Status

Service methods are stubbed and throw clear Phase 2 messages so UI/API contracts can be wired without fake SNMP noise.

## Phase 2 Implementation Plan

1. Prefer `FreeDSx/SNMP` or `snmp` PHP extension adapter behind an interface
2. Wrap transport timeouts/retries from Settings
3. Normalize vendor quirks (Huawei/MikroTik/Cisco OID variations) in mappers
4. Persist poll success/failure into device reachability + audit/snmp error logs

## UI Hooks Already Prepared

- Device show page includes disabled **Test SNMP** button
- Sidebar reserves OID Explorer entry
