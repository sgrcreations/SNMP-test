# 14 — Security

## Controls Implemented

- CSRF on web forms
- Form request validation
- Policies + Spatie permissions/gates
- Sanctum token auth for API
- Encrypted SNMP credential casts
- Secrets excluded from API resources and audit logs
- Soft deletes for devices
- Auth success/failure audit events

## Still Planned

- Dedicated SNMP error / polling error log channels UI
- Rate limiting hardening on `/api/v1/auth/login`
- Optional IP allow-lists for management plane
- Secret rotation tooling

## Role Matrix (Seed)

| Capability | admin | operator | viewer |
| --- | --- | --- | --- |
| dashboard.view | ✓ | ✓ | ✓ |
| devices.* | ✓ | create/update | view |
| settings.* | ✓ | view | |
| alerts.* | ✓ | view/ack/resolve | view |
| api.access | ✓ | ✓ | |
