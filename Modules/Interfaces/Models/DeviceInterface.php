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
        'is_uplink',
        'port_role',
        'rx_bps',
        'tx_bps',
        'rx_power_dbm',
        'tx_power_dbm',
        'temperature',
        'onu_online',
        'onu_total',
        'last_polled_at',
    ];

    protected function casts(): array
    {
        return [
            'if_index' => 'integer',
            'speed' => 'integer',
            'errors' => 'integer',
            'utilization' => 'float',
            'is_uplink' => 'boolean',
            'rx_power_dbm' => 'float',
            'tx_power_dbm' => 'float',
            'temperature' => 'float',
            'onu_online' => 'integer',
            'onu_total' => 'integer',
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

    public function speedLabel(): string
    {
        if (! $this->speed) {
            return '—';
        }

        if ($this->speed >= 1_000_000_000) {
            return round($this->speed / 1_000_000_000).'G';
        }

        if ($this->speed >= 1_000_000) {
            return round($this->speed / 1_000_000).'M';
        }

        return (string) $this->speed;
    }
}
