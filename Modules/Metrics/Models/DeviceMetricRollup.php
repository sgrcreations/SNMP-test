<?php

namespace Modules\Metrics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Devices\Models\Device;

class DeviceMetricRollup extends Model
{
    protected $fillable = [
        'device_id',
        'period',
        'bucket_at',
        'cpu_avg',
        'cpu_max',
        'memory_avg',
        'memory_max',
        'temperature_avg',
        'temperature_max',
        'samples',
    ];

    protected function casts(): array
    {
        return [
            'bucket_at' => 'datetime',
            'cpu_avg' => 'float',
            'cpu_max' => 'float',
            'memory_avg' => 'float',
            'memory_max' => 'float',
            'temperature_avg' => 'float',
            'temperature_max' => 'float',
            'samples' => 'integer',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
