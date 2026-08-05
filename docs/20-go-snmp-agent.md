# 20 — Go SNMP Agent (on-prem)

The production polling engine for customer sites lives in the sibling repo:

`../snmp-agent` (module `github.com/sgr/snmp-agent`)

## Split of responsibility

| Concern | Laravel (this app) | Go agent |
| --- | --- | --- |
| User login / RBAC | Yes | No |
| Device add/edit UI | Yes (panel) | No UI |
| SNMP polling | Optional/lab | Primary |
| Metrics / alerts store | Lab SQLite | Encrypted on-prem DB |
| Customer UI charts | Fetch via API docs | Serves `/v1/*` |

## Auth between systems

Recommended: shared API key over private network/VPN, preferably HMAC-signed requests.

Configure in Laravel settings (encrypted):

- `snmp_agent_url` → e.g. `http://10.0.0.50:9080`
- `snmp_agent_api_key` → matches agent `api_key`

UI: **System → Agent Updates** proxies:

- `GET /v1/updates/status`
- `POST /v1/updates/check`
- `POST /v1/updates/apply`

Install + publish channel: `../snmp-agent/docs/UPDATES.md`

See agent docs: `../snmp-agent/docs/API.md`

## Sync flow

1. Operator adds/edits/deletes a device in Laravel panel
2. `DeviceService` syncs to the agent when `snmp_agent_url` + `snmp_agent_api_key` are set:
   - create/update → `POST /v1/devices` with `external_id = (string) laravel_devices.id` (includes SNMP secrets once)
   - delete → `DELETE /v1/devices/by-external/{id}`
3. Agent polls on schedule into encrypted store
4. Laravel dashboard / ISP UI reads metrics from agent APIs (server-side proxy)
5. Manual bulk repair: **Agent Updates → Sync devices now**

## What customers receive

Only the compiled `snmpd` binary + example config + API doc. Not Go source.
