<?php

namespace Modules\Settings\Services;

use Illuminate\Support\Collection;
use Modules\Authentication\Services\AuditLogService;
use Modules\Settings\Repositories\Contracts\SettingRepositoryInterface;

class SettingService
{
    public function __construct(
        private readonly SettingRepositoryInterface $settings,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function allGrouped(): Collection
    {
        return $this->settings->all()->groupBy('group');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings->findByKey($key);

        return $setting ? $setting->typedValue() : $default;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function updateMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $existing = $this->settings->findByKey($key);

            if (! $existing) {
                continue;
            }

            $old = $existing->is_encrypted ? '[encrypted]' : $existing->typedValue();

            $this->settings->upsert($key, $value, [
                'group' => $existing->group,
                'type' => $existing->type,
                'label' => $existing->label,
                'description' => $existing->description,
                'is_encrypted' => $existing->is_encrypted,
            ]);

            $this->auditLogs->log(
                event: 'settings.updated',
                newValues: ['key' => $key, 'value' => $existing->is_encrypted ? '[encrypted]' : $value],
                oldValues: ['key' => $key, 'value' => $old],
                description: "Setting {$key} was updated.",
            );
        }
    }
}
