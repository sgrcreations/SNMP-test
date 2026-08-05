# 12 — Deployment

## Local (SQLite)

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

For automatic SNMP polling you also need workers:

```bash
composer run workers
# or separately:
php artisan queue:work --tries=2 --timeout=90
php artisan schedule:work
```

## Windows local PC (metrics not updating)

If the UI works but devices never poll, see **[19-windows-polling.md](19-windows-polling.md)**.

Quick fix on Windows: keep this running in a separate terminal:

```bat
composer run workers
```

Or double-click `scripts\windows-start-workers.bat`.

## Production (MySQL + Redis)

1. Set `.env`:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `DB_CONNECTION=mysql` (+ credentials)
   - `CACHE_STORE=redis`
   - `QUEUE_CONNECTION=redis`
   - `SESSION_DRIVER=redis`
2. `php artisan migrate --force`
3. `php artisan db:seed --force` (first deploy)
4. `npm ci && npm run build`
5. Configure workers:
   - `php artisan queue:work`
   - Scheduler cron: `* * * * * php /path/artisan schedule:run`
6. Serve via Nginx/Octane + PHP-FPM

## Health

Laravel health endpoint: `/up`
