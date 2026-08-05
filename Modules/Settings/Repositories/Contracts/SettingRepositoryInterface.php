<?php

namespace Modules\Settings\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Settings\Models\Setting;

interface SettingRepositoryInterface extends RepositoryInterface
{
    public function findByKey(string $key): ?Setting;

    public function upsert(string $key, mixed $value, array $attributes = []): Setting;

    public function byGroup(string $group): Collection;
}
