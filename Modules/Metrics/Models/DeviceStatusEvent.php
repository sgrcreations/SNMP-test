<?php

namespace Modules\Metrics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Devices\Models\Device;

class DeviceStatusEvent extends Model
{
    protected $fillable = [
        'device_id',
        'category',
        'severity',
        'title',
        'message',
        'meta',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
