# SNMP Monitor

Standalone SNMP monitoring lab built with Laravel, Blade, Tailwind, and a modular architecture.

## Phase 1 Ready

Authentication, RBAC, dashboard, device CRUD, settings, audit logs, and `/api/v1`.

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Login: `admin@snmpmonitor.test` / `password`

## Documentation

See [`docs/01-project-overview.md`](docs/01-project-overview.md) for the full guide index.
