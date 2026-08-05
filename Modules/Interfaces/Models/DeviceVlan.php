<?php

namespace Modules\Interfaces\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Devices\Models\Device;

class DeviceVlan extends Model
{
    protected $fillable = [
        'device_id',
        'vlan_id',
        'name',
        'status',
        'member_ports',
    ];

    protected function casts(): array
    {
        return [
            'vlan_id' => 'integer',
            'member_ports' => 'integer',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
