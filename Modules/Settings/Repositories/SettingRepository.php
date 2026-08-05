<?php

namespace Modules\Settings\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Crypt;
use Modules\Settings\Models\Setting;
use Modules\Settings\Repositories\Contracts\SettingRepositoryInterface;

class SettingRepository extends BaseRepository implements SettingRepositoryInterface
{
    public function __construct(Setting $model)
    {
        parent::__construct($model);
    }

    public function findByKey(string $key): ?Setting
    {
        return $this->query()->where('key', $key)->first();
    }

    public function upsert(string $key, mixed $value, array $attributes = []): Setting
    {
        $setting = $this->findByKey($key);

        $isEncrypted = (bool) ($attributes['is_encrypted'] ?? $setting?->is_encrypted ?? false);
        $storedValue = is_array($value) || is_object($value)
            ? json_encode($value)
            : (string) $value;

        if ($isEncrypted) {
            $storedValue = Crypt::encryptString($storedValue);
        }

        $payload = array_merge([
            'key' => $key,
            'value' => $storedValue,
            'is_encrypted' => $isEncrypted,
        ], $attributes);

        if ($setting) {
            $setting->update($payload);

            return $setting->fresh();
        }

        /** @var Setting $created */
        $created = $this->create($payload);

        return $created;
    }

    public function byGroup(string $group): Collection
    {
        return $this->query()->where('group', $group)->orderBy('key')->get();
    }
}
