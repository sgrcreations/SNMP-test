# Windows local polling (same LAN PC)

If the website opens but **metrics never update**, the Windows PC is usually missing two background processes:

1. **Queue worker** — runs SNMP poll jobs  
2. **Scheduler** — every minute runs `devices:poll`

The browser alone does **not** poll devices.

## Fastest fix (recommended)

On the Windows computer, open **two Command Prompt / PowerShell windows** in the project folder.

### Window A — website
```bat
php artisan serve --host=0.0.0.0 --port=8000
```

### Window B — polling workers
Double-click:

`scripts\windows-start-workers.bat`

or run:

```bat
composer run workers
```

Leave **Window B open**. Closing it stops polling.

## One-time manual poll (test now)

```bat
php artisan devices:poll --sync
```

or double-click:

`scripts\windows-poll-once.bat`

If sync poll works, but automatic minutes do not, Window B is not running.

## Required `.env` on Windows

```env
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

Then:

```bat
php artisan migrate --force
php artisan config:clear
```

## Settings check in UI

1. **Settings → Polling Enabled = Enabled**
2. Device **Status = Active**
3. IP must be reachable from that Windows PC

Test from Windows CMD:

```bat
ping 103.98.208.221
snmpget -v2c -c public 103.98.208.221 1.3.6.1.2.1.1.5.0
```

(`snmpget` needs Net-SNMP tools installed; optional if sync poll already works)

## Logs if still empty

```
storage\logs\snmp-YYYY-MM-DD.log
storage\logs\laravel.log
```

Also check failed jobs:

```bat
php artisan queue:failed
```

## Optional: auto-start on Windows login

Use Task Scheduler to run at logon:

```
Program: php
Arguments: artisan queue:work --tries=2 --timeout=90
Start in: C:\path\to\SNMP

Program: php
Arguments: artisan schedule:work
Start in: C:\path\to\SNMP
```

Or one task that runs `scripts\windows-start-workers.bat`.
