# 10 — Alert Engine

## Alert Types

- Device Offline
- CPU High
- Memory High
- Interface Down
- High Bandwidth
- Temperature High

## Status Workflow

`open` → `acknowledged` → `resolved`

## Threshold Sources

Settings module keys:

- `cpu_threshold`
- `memory_threshold`
- `temperature_threshold`
- `bandwidth_threshold`

## Implementation Plan (Phase 2)

1. Evaluate after each successful/failed poll
2. Deduplicate open alerts by device/type/fingerprint
3. Persist to `alerts` table
4. Surface counts on dashboard + `/api/v1/alerts`
5. Allow acknowledge/resolve via policy-protected actions

## Phase 1 Status

Dashboard shows Open Alerts as `0` placeholder.
Permissions already include `alerts.view`, `alerts.acknowledge`, `alerts.resolve`.
