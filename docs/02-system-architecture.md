# 02 — System Architecture

## Architectural Style

SNMP Monitor uses a **modular monolith**:

- One deployable Laravel app
- Domain modules under `Modules/{Name}`
- Shared primitives under `app/Core`
- Module auto-discovery via `App\Providers\ModuleServiceProvider`

This keeps boundaries clear for later extraction (SNMP worker, metrics store) without premature microservices.

## Layering

```
HTTP (Controllers / Form Requests / API Resources)
        ↓
Application Services (use-cases, orchestration)
        ↓
Repositories (persistence abstraction)
        ↓
Eloquent Models / DB
```

Cross-cutting concerns:

- Policies + Spatie permissions
- Audit logging service
- Encrypted model casts for SNMP secrets

## Module Map

| Module | Responsibility | Phase |
| --- | --- | --- |
| Authentication | Roles, permissions, audit logs | 1 |
| Dashboard | Ops overview | 1 |
| Devices | Inventory + credential storage | 1 |
| Settings | Platform thresholds/config | 1 |
| Api | Versioned REST surface | 1 |
| SNMP | GET/WALK/Test Connection | 2 |
| Interfaces | Interface inventory | 2 |
| Metrics | Historical telemetry | 2 |
| Alerts | Threshold evaluation | 2 |
| Reports | Exports & summaries | 3 |

## Request Flow (Device Create)

1. `StoreDeviceRequest` validates + authorizes
2. `DeviceController` maps to `DeviceData` DTO
3. `DeviceService` persists through `DeviceRepository`
4. `AuditLogService` records sanitized mutation
5. User redirected to device show page

## Polling Architecture (Phase 2 design)

- Scheduler ticks every minute
- Active devices dispatched to queue jobs
- Jobs call `SNMPService`
- Metrics written to `device_metrics` / `interface_metrics`
- Alert engine evaluates thresholds asynchronously

UI never performs live blocking SNMP walks for polling.
