<?php

namespace Modules\Devices\Database\Factories;

use App\Core\Enums\DeviceStatus;
use App\Core\Enums\DeviceVendor;
use App\Core\Enums\SnmpVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Devices\Models\Device;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->domainWord().'-router',
            'vendor' => fake()->randomElement(DeviceVendor::cases())->value,
            'model' => fake()->bothify('SRC-####'),
            'hostname' => fake()->domainName(),
            'ip_address' => fake()->unique()->ipv4(),
            'snmp_version' => SnmpVersion::V2c->value,
            'community' => 'public',
            'port' => 161,
            'location' => fake()->city(),
            'description' => fake()->sentence(),
            'polling_interval' => 60,
            'status' => DeviceStatus::Active->value,
            'reachability' => DeviceStatus::Unknown->value,
        ];
    }
}
