<?php

namespace Modules\Interfaces\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Devices\Models\Device;

class DeviceOnu extends Model
{
    protected $table = 'device_onus';

    protected $fillable = [
        'device_id',
        'device_interface_id',
        'serial',
        'description',
        'pon_port',
        'onu_id',
        'status',
        'rx_power_dbm',
        'tx_power_dbm',
        'distance_m',
        'temperature',
        'customer',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'onu_id' => 'integer',
            'rx_power_dbm' => 'float',
            'tx_power_dbm' => 'float',
            'distance_m' => 'integer',
            'temperature' => 'float',
            'last_seen_at' => 'datetime',
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
