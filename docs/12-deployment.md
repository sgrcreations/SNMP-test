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
