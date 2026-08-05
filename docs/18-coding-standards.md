# 18 — Coding Standards

## Architecture Rules

- Repository Pattern for persistence
- Service Layer for use-cases
- DTOs for structured writes (`DeviceData`, SNMP DTOs)
- Form Requests for validation/authorization entry
- API Resources for response shaping
- Policies + permission gates
- Constructor dependency injection
- SOLID, PSR-12, no copy-paste across modules

## TypeScript Exhaustive Switch Equivalent (PHP)

For enums, prefer `match` with exhaustive cases and no silent defaults when adding variants would change behavior.

## Imports

Keep imports at the top of PHP files. Avoid inline imports unless documenting a circular-dependency exception.

## Laravel Conventions

- Explicit route names (`devices.*`, `api.v1.*`)
- Feature flags/settings for operational toggles
- Queue for any network I/O that can exceed request budgets
- Never log decrypted SNMP secrets

## UI Standards

- Enterprise layout via `<x-monitor-layout>`
- Responsive tables/filters
- Dark mode class strategy
- Accessible labels on forms
- Cards only when they group actionable or scannable metrics

## Documentation Rule

Every feature PR updates the matching docs in `/docs`.
