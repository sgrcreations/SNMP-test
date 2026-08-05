<?php

namespace Modules\Metrics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Devices\Models\Device;

class PingSample extends Model
{
    protected $fillable = [
        'device_id',
        'latency_ms',
        'jitter_ms',
        'packet_loss_pct',
        'packets_sent',
        'packets_received',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latency_ms' => 'float',
            'jitter_ms' => 'float',
            'packet_loss_pct' => 'float',
            'packets_sent' => 'integer',
            'packets_received' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
