# Single-store metrics (agent hot + Laravel rollups)

When **snmp-agent is configured**, time-series are not duplicated every minute on Laravel.

| Store | What it keeps |
| --- | --- |
| **snmp-agent SQLite** | Hot CPU/mem/temp (+ IF counters), ~48h (`metrics_retention`) |
| **Laravel** | Device status (`last_*`), interface inventory, poll logs, compact `device_metric_rollups` (5m/1h) |

## Behaviour

- Scheduled `devices:poll` (**every minute**) reconciles status **and** pulls agent interface inventory into Laravel (ports, util, uplink rates, samples).
- Device Overview while open refreshes every `max(15, min(interval, 60))` seconds via `metrics.json` (pulls agent IF inventory, updates KPIs / fabric / uplink).
- Manual **Sync** still forces a full agent poll + VLAN Q-BRIDGE merge when the updated agent is deployed.
- `metrics:rollup` (hourly) builds 5m/1h rollups for 7d/30d charts; prunes rollups older than 30 days.
- Without agent: previous Laravel poll + local `device_metrics` behaviour unchanged (`--force-poll` forces that path even with agent configured).

Polling interval is shown on Overview + Polling Health (“Polls every 1 minute · Source: snmp-agent”).

**Requirement:** Laravel scheduler must be running (`* * * * * php artisan schedule:run`) so background reconcile keeps Ports/Dashboard fresh even when Overview is closed.
