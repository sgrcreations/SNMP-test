# 17 — Changelog

## 0.2.0 — Phase 2 Polling (2026-08-05)

### Added

- FreeDSx SNMP client integration (`SNMPService`)
- Scheduler command `devices:poll` (every minute)
- Queue job `PollDeviceJob`
- Tables: `device_interfaces`, `device_metrics`, `interface_metrics`
- Device **Test SNMP** UI action
- Interfaces inventory page
- SNMP daily log channel (`storage/logs/snmp-*.log`)

## 0.1.0 — Phase 1 Foundation (2026-08-05)

### Added

- Laravel application scaffold with Breeze Blade auth
- Modular architecture under `Modules/`
- Spatie roles/permissions (`admin`, `operator`, `viewer`)
- Device inventory CRUD with SNMP v2c/v3 fields
- Encrypted SNMP credentials
- Settings module with seeded defaults
- Audit logging for auth + device mutations
- Dashboard overview UI (dark/light)
- REST API `/api/v1` for auth, dashboard, devices
- SNMP service stubs and DTOs
- Phase 2/3 module stubs
- Test coverage for dashboard/devices/API
- Complete `/docs` documentation set
