<?php

namespace Modules\Interfaces\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Devices\Models\Device;
use Modules\Metrics\Models\InterfaceMetric;

class DeviceInterface extends Model
{
    protected $table = 'device_interfaces';

    protected $fillable = [
        'device_id',
        'if_index',
        'name',
        'description',
        'oper_status',
        'speed',
        'rx_bytes',
        'tx_bytes',
        'errors',
        'utilization',
        'last_polled_at',
    ];

    protected function casts(): array
    {
        return [
            'if_index' => 'integer',
            'speed' => 'integer',
            'errors' => 'integer',
            'utilization' => 'float',
            'last_polled_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(InterfaceMetric::class);
    }
}
