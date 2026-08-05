# 09 — Polling Engine

## Design Goals

- Never block the web UI
- Honor per-device `polling_interval`
- Support global enable/disable via Settings (`polling_enabled`)
- Failures recorded without stopping the schedule batch

## Planned Flow (Phase 2)

1. `routes/console.php` schedules `devices:poll` every minute
2. Command selects active devices due for poll
3. Each device dispatched as `PollDeviceJob`
4. Job uses `SNMPService` + vendor mapper
5. Metrics repositories store samples
6. Device `reachability`, `last_polled_at`, `last_seen_at` updated
7. Alert evaluator consumes fresh samples

## Settings Used

- `polling_enabled`
- `default_polling_interval`
- `snmp_timeout`
- `snmp_retries`

## Phase 1 Status

Scaffold only. Queue tables and scheduler are available; polling command/jobs arrive in Phase 2.
