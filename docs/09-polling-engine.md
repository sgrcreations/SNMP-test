# 09 — Polling Engine

## How it works (live)

1. Laravel Scheduler runs `devices:poll` every minute
2. Command selects **active** devices that are due (`last_polled_at` + `polling_interval`)
3. Each device is dispatched as `PollDeviceJob` (queued)
4. Job calls `DevicePollService` → `SNMPService` (FreeDSx)
5. Results are stored in:
   - `device_metrics`
   - `device_interfaces`
   - `interface_metrics`
6. Device `reachability`, `last_polled_at`, and `last_seen_at` are updated

## What you must run in terminals

Keep these processes running while developing/operating:

```bash
# Terminal 1 — web app
php artisan serve

# Terminal 2 — queue worker (required for async polls)
php artisan queue:work --tries=2 --timeout=90

# Terminal 3 — scheduler (runs devices:poll every minute)
php artisan schedule:work
```

Or use the bundled Composer script:

```bash
composer run dev
```

That starts server + queue + logs + Vite together. You still need the scheduler:

```bash
php artisan schedule:work
```

## Manual one-shot poll

```bash
# Queue jobs for due devices
php artisan devices:poll

# Poll inline (no queue worker needed)
php artisan devices:poll --sync

# Poll one device
php artisan devices:poll --sync --device=1
```

## Settings

- `polling_enabled` must be **Enabled** in Settings
- Per-device `polling_interval` (seconds) controls due-time
- `snmp_timeout` / `snmp_retries` control FreeDSx timeouts

## Reachability

Private LAN devices only work if this Mac/server can UDP-reach them (same network or VPN).

## Logs

SNMP and polling errors: `storage/logs/snmp-*.log`
