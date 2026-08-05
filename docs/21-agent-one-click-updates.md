# Agent updates (git → Check → Make update)

No browser file upload. Releases are published by git/CI; operators only click **Check** then **Make update**.

## Flow

```
git push / deploy
   ↓
scripts/publish-agent-from-git.sh   (or: php artisan agent:publish-release …)
   ↓
Laravel hosts signed manifest + binary
   ↓
UI: Point agent at channel (once)
   ↓
UI: Check for updates → Make update
```

## One-time

1. Laravel `.env`:
```env
SNMP_UPDATE_PRIVATE_KEY_B64=<ed25519 private key base64>
```
2. Settings → agent URL + API key  
3. Agent Updates → **Point agent at this Laravel channel**  
4. Agent binary `0.1.2+` (supports channel API)

## After each agent code change

On the machine that has Laravel + snmp-agent source (or CI):

```bash
cd /path/to/SNMP
VERSION=0.1.3 ./scripts/publish-agent-from-git.sh
```

Or explicitly:

```bash
cd ../snmp-agent && make release VERSION=0.1.3
cd ../SNMP
php artisan agent:publish-release 0.1.3 ../snmp-agent/dist/snmpd-linux-amd64 --push-channel
```

Then in the UI:

1. **Check for updates**  
2. **Make update**

## Channel URL

`https://your-laravel-host/updates/snmp-agent/linux-amd64/manifest.json`
