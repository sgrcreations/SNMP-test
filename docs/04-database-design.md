# 04 — Database Design

## Core Tables (Phase 1)

### users
Laravel Breeze users + Sanctum tokens + Spatie roles.

### permission tables
Managed by `spatie/laravel-permission`.

### devices
Network endpoint inventory.

| Column | Notes |
| --- | --- |
| name, vendor, model, hostname | Identity |
| ip_address, port | Unique endpoint |
| snmp_version | `v2c` or `v3` |
| community, auth_*, priv_* | Encrypted casts |
| polling_interval | Seconds |
| status | `active` / `inactive` |
| reachability | `online` / `offline` / `unknown` |
| last_polled_at, last_seen_at | Filled by Phase 2 polling |
| soft deletes | Enabled |

### settings
Key/value platform configuration with groups (`polling`, `snmp`, `alerts`, `general`).

### audit_logs
Auth events and entity mutations (sanitized payloads, no secrets).

## Planned Tables (Phase 2)

### device_metrics
`device_id`, `cpu`, `memory`, `temperature`, `uptime`, `recorded_at`

### interface_metrics
`device_id`, `interface_id`, `rx_bytes`, `tx_bytes`, `errors`, `utilization`, `recorded_at`

### device_interfaces
Canonical interface inventory from IF-MIB.

### alerts
Threshold and reachability events with status workflow (`open`, `acknowledged`, `resolved`).

Migrations are MySQL-compatible and run on SQLite for local development.
