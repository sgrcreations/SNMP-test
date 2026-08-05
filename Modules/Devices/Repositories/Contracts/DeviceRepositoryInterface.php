<?php

namespace Modules\Devices\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DeviceRepositoryInterface extends RepositoryInterface
{
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function countByStatus(): array;

    public function countByReachability(): array;

    public function pollable(): Collection;
}
