<?php

namespace Modules\Devices\Models;

use App\Core\Enums\DeviceStatus;
use App\Core\Enums\DeviceVendor;
use App\Core\Enums\SnmpAuthProtocol;
use App\Core\Enums\SnmpPrivProtocol;
use App\Core\Enums\SnmpVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Devices\Database\Factories\DeviceFactory;

class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'vendor',
        'model',
        'hostname',
        'ip_address',
        'snmp_version',
        'community',
        'username',
        'auth_protocol',
        'auth_password',
        'priv_protocol',
        'priv_password',
        'port',
        'location',
        'description',
        'polling_interval',
        'status',
        'reachability',
        'last_polled_at',
        'last_seen_at',
        'created_by',
    ];

    protected $hidden = [
        'community',
        'auth_password',
        'priv_password',
    ];

    protected function casts(): array
    {
        return [
            'vendor' => DeviceVendor::class,
            'snmp_version' => SnmpVersion::class,
            'auth_protocol' => SnmpAuthProtocol::class,
            'priv_protocol' => SnmpPrivProtocol::class,
            'status' => DeviceStatus::class,
            'reachability' => DeviceStatus::class,
            'community' => 'encrypted',
            'auth_password' => 'encrypted',
            'priv_password' => 'encrypted',
            'port' => 'integer',
            'polling_interval' => 'integer',
            'last_polled_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    protected static function newFactory(): DeviceFactory
    {
        return DeviceFactory::new();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSnmpV3(): bool
    {
        return $this->snmp_version === SnmpVersion::V3;
    }

    public function isPollable(): bool
    {
        return $this->status === DeviceStatus::Active;
    }

    public function displayEndpoint(): string
    {
        return sprintf('%s:%d', $this->ip_address, $this->port);
    }
}
