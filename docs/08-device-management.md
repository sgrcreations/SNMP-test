# 08 — Device Management

## Capabilities (Phase 1)

- Create / read / update / soft-delete devices
- Filter and search inventory
- Store SNMP v2c and v3 profiles
- Encrypt community + SNMPv3 passwords
- Authorization via permissions + `DevicePolicy`

## Fields

Name, Vendor, Model, Hostname, IP Address, SNMP Version, Community, Username, Authentication Protocol/Password, Privacy Protocol/Password, Port, Location, Description, Polling Interval, Status.

## Vendors

Huawei, MikroTik, Cisco, Juniper, Fortinet, Aruba, Ubiquiti, Generic SNMP.

## UI Routes

| Route | Description |
| --- | --- |
| `/devices` | Inventory table |
| `/devices/create` | Create form |
| `/devices/{id}` | Detail |
| `/devices/{id}/edit` | Edit form |

## Credential Handling

- Secrets hidden from API resources and audit payloads
- Blank password fields on edit keep previous values
- Laravel `encrypted` casts protect values at rest

## Next

- Test SNMP button live results
- Per-device OID Explorer deep link
- Poll schedule awareness widgets
