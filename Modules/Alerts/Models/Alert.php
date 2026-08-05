<?php

namespace Modules\Alerts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Devices\Models\Device;
use Modules\Interfaces\Models\DeviceInterface;

class Alert extends Model
{
    protected $fillable = [
        'device_id',
        'device_interface_id',
        'type',
        'severity',
        'status',
        'title',
        'message',
        'raised_at',
        'acknowledged_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'raised_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
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
