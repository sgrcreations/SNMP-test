<?php

namespace Modules\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishAgentReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'regex:/^\d+\.\d+\.\d+$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'os' => ['nullable', 'in:linux'],
            'arch' => ['nullable', 'in:amd64,arm64'],
            'binary' => ['required', 'file', 'max:102400'], // 100MB
        ];
    }
}
