<?php

namespace Modules\Devices\Models;

use App\Core\Enums\DeviceStatus;
use App\Core\Enums\DeviceType;
use App\Core\Enums\DeviceVendor;
use App\Core\Enums\SnmpAuthProtocol;
use App\Core\Enums\SnmpPrivProtocol;
use App\Core\Enums\SnmpVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Alerts\Models\Alert;
use Modules\Devices\Database\Factories\DeviceFactory;
use Modules\Interfaces\Models\DeviceInterface;
use Modules\Interfaces\Models\DeviceOnu;
use Modules\Interfaces\Models\DeviceVlan;
use Modules\Metrics\Models\DeviceMetric;
use Modules\Metrics\Models\DeviceStatusEvent;
use Modules\Metrics\Models\PingSample;
use Modules\Metrics\Models\PollLog;

class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'vendor',
        'device_type',
        'model',
        'serial_number',
        'firmware',
        'manufacturer',
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
        'area',
        'latitude',
        'longitude',
        'description',
        'polling_interval',
        'status',
        'reachability',
        'interface_count',
        'last_cpu',
        'last_memory',
        'last_temperature',
        'sys_uptime',
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
            'device_type' => DeviceType::class,
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
            'latitude' => 'float',
            'longitude' => 'float',
            'interface_count' => 'integer',
            'last_cpu' => 'float',
            'last_memory' => 'float',
            'last_temperature' => 'float',
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

    public function interfaces(): HasMany
    {
        return $this->hasMany(DeviceInterface::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(DeviceMetric::class);
    }

    public function pollLogs(): HasMany
    {
        return $this->hasMany(PollLog::class);
    }

    public function onus(): HasMany
    {
        return $this->hasMany(DeviceOnu::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function vlans(): HasMany
    {
        return $this->hasMany(DeviceVlan::class);
    }

    public function pingSamples(): HasMany
    {
        return $this->hasMany(PingSample::class);
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(DeviceStatusEvent::class);
    }

    public function isOlt(): bool
    {
        return $this->device_type === DeviceType::Olt;
    }

    /**
     * Whether this device should render the OLT monitoring workspace.
     */
    public function hasOltWorkspace(): bool
    {
        return $this->isOlt();
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
