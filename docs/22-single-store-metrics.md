# Single-store metrics (agent hot + Laravel rollups)

When **snmp-agent is configured**, time-series are not duplicated every minute on Laravel.

| Store | What it keeps |
| --- | --- |
| **snmp-agent SQLite** | Hot CPU/mem/temp (+ IF counters), ~48h (`metrics_retention`) |
| **Laravel** | Device status (`last_*`), interface inventory, poll logs, compact `device_metric_rollups` (5m/1h) |

## Behaviour

- Scheduled `devices:poll` **reconciles status** from the agent (no SNMP from Laravel, no `device_metrics` rows).
- Manual **Sync** still triggers agent poll and updates Laravel status/interfaces + one poll log — still **no** history insert.
- Device Overview charts read agent metrics (proxy); while the page is open they refresh every `max(15, min(interval, 60))` seconds.
- `metrics:rollup` (hourly) builds 5m/1h rollups for 7d/30d charts; prunes rollups older than 30 days.
- Without agent: previous Laravel poll + local `device_metrics` behaviour unchanged (`--force-poll` forces that path even with agent configured).

Polling interval is shown on Overview + Polling Health (“Polls every 1 minute · Source: snmp-agent”).
