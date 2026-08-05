<?php

namespace Modules\Devices\Requests;

use App\Core\Enums\DeviceStatus;
use App\Core\Enums\DeviceType;
use App\Core\Enums\DeviceVendor;
use App\Core\Enums\SnmpAuthProtocol;
use App\Core\Enums\SnmpPrivProtocol;
use App\Core\Enums\SnmpVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('devices.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'vendor' => ['required', Rule::enum(DeviceVendor::class)],
            'device_type' => ['nullable', Rule::enum(DeviceType::class)],
            'model' => ['nullable', 'string', 'max:255'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'snmp_version' => ['required', Rule::enum(SnmpVersion::class)],
            'community' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'required_if:snmp_version,v3', 'string', 'max:255'],
            'auth_protocol' => ['nullable', 'required_if:snmp_version,v3', Rule::enum(SnmpAuthProtocol::class)],
            'auth_password' => ['nullable', 'string', 'max:255'],
            'priv_protocol' => ['nullable', Rule::enum(SnmpPrivProtocol::class)],
            'priv_password' => ['nullable', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'location' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'polling_interval' => ['required', 'integer', 'min:30', 'max:86400'],
            'status' => ['required', Rule::in([DeviceStatus::Active->value, DeviceStatus::Inactive->value])],
        ];
    }
}
