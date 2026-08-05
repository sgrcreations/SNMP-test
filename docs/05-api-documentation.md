# 05 — API Documentation

Base path: `/api/v1`

Auth: Laravel Sanctum bearer tokens.

## Auth

### POST `/api/v1/auth/login`
```json
{
  "email": "admin@snmpmonitor.test",
  "password": "password",
  "device_name": "postman"
}
```

Response includes `token`.

### GET `/api/v1/auth/me`
Bearer required. Returns user, roles, permissions.

### POST `/api/v1/auth/logout`
Revokes current token.

## Dashboard

### GET `/api/v1/dashboard`
Returns overview stats.

## Devices

| Method | Path | Permission |
| --- | --- | --- |
| GET | `/api/v1/devices` | `devices.view` |
| POST | `/api/v1/devices` | `devices.create` |
| GET | `/api/v1/devices/{id}` | `devices.view` |
| PUT/PATCH | `/api/v1/devices/{id}` | `devices.update` |
| DELETE | `/api/v1/devices/{id}` | `devices.delete` |

Query filters on index: `search`, `vendor`, `status`, `reachability`, `snmp_version`, `per_page`.

Secrets (`community`, passwords) are never returned by `DeviceResource`.

## Stubs (501)

- `/api/v1/interfaces`
- `/api/v1/metrics`
- `/api/v1/alerts`
