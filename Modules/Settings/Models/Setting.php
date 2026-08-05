<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'description',
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public function typedValue(): mixed
    {
        $raw = $this->value;

        if ($this->is_encrypted && filled($raw)) {
            try {
                $raw = Crypt::decryptString($raw);
            } catch (\Throwable) {
                // Value may already be plaintext during seeding transitions.
            }
        }

        return match ($this->type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $raw,
            'float' => (float) $raw,
            'json' => json_decode((string) $raw, true),
            default => $raw,
        };
    }
}
