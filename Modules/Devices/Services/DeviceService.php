<?php

namespace Modules\Devices\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authentication\Services\AuditLogService;
use Modules\Devices\Dto\DeviceData;
use Modules\Devices\Models\Device;
use Modules\Devices\Repositories\Contracts\DeviceRepositoryInterface;

class DeviceService
{
    public function __construct(
        private readonly DeviceRepositoryInterface $devices,
        private readonly AuditLogService $auditLogs,
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
     * @return array<string, mixed>
     */
    private function safeAttributes(Device $device): array
    {
        return collect($device->toArray())
            ->except(['community', 'auth_password', 'priv_password'])
            ->all();
    }
}
