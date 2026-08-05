# 16 — Contributing

## Principles

1. Keep modules cohesive; shared code goes in `app/Core`
2. Controllers stay thin; business rules live in Services
3. Persist via Repositories
4. Validate with Form Requests
5. Expose API through Resources
6. Authorize with Policies/Permissions
7. Update `/docs` in the same change set
8. Add/adjust tests for every feature

## Workflow

1. Create feature branch
2. Implement module changes
3. `php artisan test`
4. `npm run build` when UI/CSS changes
5. Update relevant docs (`01`–`18`)
6. Open PR with architecture notes for major components

## Coding Style

- Laravel Pint defaults
- PSR-12
- Enums for closed value sets
- No duplicated SNMP logic outside `SNMPService`
