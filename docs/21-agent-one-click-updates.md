# One-click agent updates via Laravel

After the agent is installed once on the VPS, you do **not** SCP for every release.

## One-time setup

### 1) Laravel `.env` (control plane)

```env
SNMP_UPDATE_PRIVATE_KEY_B64=<base64 Ed25519 private key>
```

Use the same keypair as `snmp-agent/keys/` (public key is already embedded in the agent).

### 2) Deploy Laravel code that includes Agent Updates publish UI.

### 3) Settings → agent

- `snmp_agent_url` = `http://103.181.33.22:9080`
- `snmp_agent_api_key` = agent API key

### 4) Install agent **0.1.2+** once (supports `POST /v1/updates/channel`)

Then forever use the UI.

## Everyday update flow

1. On Mac: `make release VERSION=0.1.3`
2. Laravel → **Agent Updates** → upload `dist/snmpd-linux-amd64`, version `0.1.3` → **Publish release**
3. Click **Point agent at Laravel channel** (auto on publish if agent reachable)
4. **Check for updates** → **Apply update**

Agent downloads from:

`https://your-laravel-host/updates/snmp-agent/linux-amd64/manifest.json`

Downtime ≈ 2–5 seconds. Failed startups roll back.
