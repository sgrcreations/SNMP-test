<?php

namespace Modules\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.polling_enabled' => ['nullable', 'in:0,1'],
            'settings.default_polling_interval' => ['nullable', 'integer', 'min:30', 'max:86400'],
            'settings.snmp_timeout' => ['nullable', 'integer', 'min:1', 'max:60'],
            'settings.snmp_retries' => ['nullable', 'integer', 'min:0', 'max:10'],
            'settings.cpu_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'settings.memory_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'settings.temperature_threshold' => ['nullable', 'integer', 'min:1', 'max:150'],
            'settings.bandwidth_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'settings.app_timezone' => ['nullable', 'timezone'],
            'settings.items_per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
