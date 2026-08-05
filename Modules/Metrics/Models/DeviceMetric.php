<?php

namespace Modules\Metrics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Devices\Models\Device;

class DeviceMetric extends Model
{
    protected $fillable = [
        'device_id',
        'cpu',
        'memory',
        'temperature',
        'rx_bytes',
        'tx_bytes',
        'uptime',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'cpu' => 'float',
            'memory' => 'float',
            'temperature' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
