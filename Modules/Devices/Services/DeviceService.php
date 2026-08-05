<?php

namespace Modules\Devices\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Modules\Authentication\Services\AuditLogService;
use Modules\Devices\Dto\DeviceData;
use Modules\Devices\Models\Device;
use Modules\Devices\Repositories\Contracts\DeviceRepositoryInterface;
use Modules\Settings\Services\SnmpAgentClient;
use Throwable;

class DeviceService
{
    public function __construct(
        private readonly DeviceRepositoryInterface $devices,
        private readonly AuditLogService $auditLogs,
        private readonly SnmpAgentClient $agent,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->devices->search($filters, $perPage);
    }

    public function find(int $id): Device
    {
        /** @var Device $device */
        $device = $this->devices->findOrFail($id);

        return $device;
    }

    public function create(DeviceData $data): Device
    {
        /** @var Device $device */
        $device = $this->devices->create($data->toArray());

        $this->auditLogs->log(
            event: 'device.created',
            auditable: $device,
            newValues: $this->safeAttributes($device),
            description: "Device {$device->name} was created.",
        );

        $this->syncUpsertToAgent($device);

        return $device;
    }

    public function update(int $id, DeviceData $data): Device
    {
        $existing = $this->find($id);
        $old = $this->safeAttributes($existing);

        $payload = $data->toArray();

        if (empty($payload['community'])) {
            unset($payload['community']);
        }

        if (empty($payload['auth_password'])) {
            unset($payload['auth_password']);
        }

        if (empty($payload['priv_password'])) {
            unset($payload['priv_password']);
        }

        /** @var Device $device */
        $device = $this->devices->update($id, $payload);

        $this->auditLogs->log(
            event: 'device.updated',
            auditable: $device,
            oldValues: $old,
            newValues: $this->safeAttributes($device),
            description: "Device {$device->name} was updated.",
        );

        $this->syncUpsertToAgent($device);

        return $device;
    }

    public function delete(int $id): bool
    {
        $device = $this->find($id);
        $snapshot = $this->safeAttributes($device);
        $deleted = $this->devices->delete($id);

        if ($deleted) {
            $this->auditLogs->log(
                event: 'device.deleted',
                auditable: $device,
                oldValues: $snapshot,
                description: "Device {$device->name} was deleted.",
            );
            $this->syncDeleteToAgent($device->id);
        }

        return $deleted;
    }

    public function stats(): array
    {
        return array_merge(
            $this->devices->countByStatus(),
            $this->devices->countByReachability(),
        );
    }

    /**
     * Push all local devices to the agent (manual repair / first-time connect).
     *
     * @return array{synced: int, failed: int, skipped: bool, errors: list<string>}
     */
    public function syncAllToAgent(): array
    {
        if (! $this->agent->configured()) {
            return ['synced' => 0, 'failed' => 0, 'skipped' => true, 'errors' => ['Agent not configured']];
        }

        $synced = 0;
        $failed = 0;
        $errors = [];

        Device::query()->orderBy('id')->each(function (Device $device) use (&$synced, &$failed, &$errors): void {
            try {
                $this->agent->upsertDevice($device);
                $synced++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = "device {$device->id}: ".$e->getMessage();
                Log::warning('snmp-agent sync failed', [
                    'device_id' => $device->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        return compact('synced', 'failed') + ['skipped' => false, 'errors' => $errors];
    }

    private function syncUpsertToAgent(Device $device): void
    {
        if (! $this->agent->configured()) {
            return;
        }

        try {
            $this->agent->upsertDevice($device->fresh() ?? $device);
        } catch (Throwable $e) {
            Log::warning('snmp-agent upsert failed', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncDeleteToAgent(int $deviceId): void
    {
        if (! $this->agent->configured()) {
            return;
        }

        try {
            $this->agent->deleteDeviceByExternalId($deviceId);
        } catch (Throwable $e) {
            // Already-deleted on agent is fine for idempotent Laravel deletes.
            if ($e->getCode() === 404) {
                return;
            }
            Log::warning('snmp-agent delete failed', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function safeAttributes(Device $device): array
    {
        return collect($device->toArray())
            ->except(['community', 'auth_password', 'priv_password'])
            ->all();
    }
}
