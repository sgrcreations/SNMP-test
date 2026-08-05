# 03 — Folder Structure

```text
app/
  Core/                      # Shared enums, base repository, contracts
  Http/                      # Breeze profile controllers
  Models/User.php            # Auth user + Sanctum + Spatie roles
  Providers/                 # App, Module, Repository providers

Modules/
  Authentication/
  Dashboard/
  Devices/
  Settings/
  Api/
  SNMP/                      # Stubbed service + DTOs
  Interfaces/                # Phase 2 stub
  Metrics/                   # Phase 2 stub
  Alerts/                    # Phase 2 stub
  Reports/                   # Phase 3 stub

Each module contains where applicable:
  Controllers/
  Models/
  Services/
  Repositories/
  Requests/
  Resources/
  Policies/
  Routes/
  Views/
  Tests/
  Database/Migrations|Seeders
  Providers/
  Dto/

resources/views/
  components/monitor-layout.blade.php
  partials/sidebar.blade.php
  partials/topbar.blade.php
  auth/, layouts/, profile/   # Breeze

docs/                         # This documentation set
database/seeders/             # Role/permission/settings seeds
routes/web.php                # Root/profile + auth stubs
```

## Autoloading

`composer.json` maps:

- `App\\` → `app/`
- `Modules\\` → `Modules/`
