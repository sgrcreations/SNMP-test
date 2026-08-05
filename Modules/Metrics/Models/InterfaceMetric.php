<?php

namespace Modules\Metrics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Devices\Models\Device;
use Modules\Interfaces\Models\DeviceInterface;

class InterfaceMetric extends Model
{
    protected $fillable = [
        'device_id',
        'device_interface_id',
        'rx_bytes',
        'tx_bytes',
        'errors',
        'utilization',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'integer',
            'utilization' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function networkInterface(): BelongsTo
    {
        return $this->belongsTo(DeviceInterface::class, 'device_interface_id');
    }
}
