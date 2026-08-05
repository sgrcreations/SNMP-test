<?php

namespace Modules\Devices\Repositories;

use App\Core\Enums\DeviceStatus;
use App\Core\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Devices\Models\Device;
use Modules\Devices\Repositories\Contracts\DeviceRepositoryInterface;

class DeviceRepository extends BaseRepository implements DeviceRepositoryInterface
{
    public function __construct(Device $model)
    {
        parent::__construct($model);
    }

    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('hostname', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['vendor'])) {
            $query->where('vendor', $filters['vendor']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['reachability'])) {
            $query->where('reachability', $filters['reachability']);
        }

        if (! empty($filters['snmp_version'])) {
            $query->where('snmp_version', $filters['snmp_version']);
        }

        if (! empty($filters['device_type'])) {
            $query->where('device_type', $filters['device_type']);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function countByStatus(): array
    {
        return [
            'total' => $this->query()->count(),
            'active' => $this->query()->where('status', DeviceStatus::Active)->count(),
            'inactive' => $this->query()->where('status', DeviceStatus::Inactive)->count(),
        ];
    }

    public function countByReachability(): array
    {
        return [
            'online' => $this->query()->where('reachability', DeviceStatus::Online)->count(),
            'offline' => $this->query()->where('reachability', DeviceStatus::Offline)->count(),
            'unknown' => $this->query()->where('reachability', DeviceStatus::Unknown)->count(),
        ];
    }

    public function pollable(): Collection
    {
        return $this->query()
            ->where('status', DeviceStatus::Active)
            ->get();
    }
}
