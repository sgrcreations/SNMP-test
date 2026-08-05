# 01 — Project Overview

## SNMP Monitor

SNMP Monitor is a standalone Laravel application for learning, testing, and validating SNMP integrations before promoting them into a larger ISP platform.

It is intentionally **not** part of an ISP ERP. The goal is a clean lab/production-grade sandbox for router polling, interface telemetry, historical metrics, alerts, and REST API consumers.

## Phase 1 Scope (Completed)

- Authentication via Laravel Breeze (Blade + Tailwind)
- Roles and permissions via Spatie Laravel Permission
- Operations dashboard shell
- Device CRUD with SNMP v2c / v3 credential fields
- Encrypted SNMP secrets at rest
- Platform settings
- Audit logging for auth and device mutations
- Versioned REST API (`/api/v1`) via Sanctum
- Modular codebase under `Modules/`
- Full documentation tree under `/docs`

## Stack

| Layer | Choice |
| --- | --- |
| Framework | Laravel 13 (current stable; PHP 8.4+/8.5 compatible) |
| Auth UI | Laravel Breeze (Blade) |
| API Auth | Laravel Sanctum |
| RBAC | Spatie Permission |
| Frontend | Blade + Tailwind + Alpine.js |
| Charts | ApexCharts (wired; charts populate in Phase 2) |
| Queue | Database/Redis ready |
| Local DB | SQLite (MySQL-ready migrations) |

## Default Users

| Email | Password | Role |
| --- | --- | --- |
| `admin@snmpmonitor.test` | `password` | admin |
| `operator@snmpmonitor.test` | `password` | operator |

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

Open `http://127.0.0.1:8000` and sign in with the admin user.
