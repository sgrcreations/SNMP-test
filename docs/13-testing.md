# 13 — Testing

## Commands

```bash
php artisan test
```

Module tests live beside features:

- `Modules/Devices/Tests`
- `Modules/Dashboard/Tests`
- `Modules/Api/Tests`

PHPUnit includes `Modules/*/Tests` via `phpunit.xml`.

## Covered in Phase 1

- Dashboard permission access
- Device index/create authorization
- API login token issuance
- API device list auth requirements
- API dashboard stats payload

## Guidance

- Prefer feature tests for HTTP/module boundaries
- Mock `SNMPService` in Phase 2 unit tests
- Keep secrets out of assertions
