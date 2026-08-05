<?php

namespace Modules\Devices\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Devices\Models\Device;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        foreach ([
            'devices.view',
            'devices.create',
            'devices.update',
            'devices.delete',
            'dashboard.view',
        ] as $permission) {
            Permission::findOrCreate($permission);
        }

        $role = Role::findOrCreate('admin');
        $role->syncPermissions(Permission::all());

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_view_devices_index(): void
    {
        $user = $this->actingAsAdmin();
        Device::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee('Device Inventory');
    }

    public function test_admin_can_create_device(): void
    {
        $user = $this->actingAsAdmin();

        $payload = [
            'name' => 'Core Router',
            'vendor' => 'mikrotik',
            'model' => 'CCR2004',
            'hostname' => 'core-1.lab',
            'ip_address' => '10.10.10.1',
            'snmp_version' => 'v2c',
            'community' => 'public',
            'port' => 161,
            'location' => 'Lab Rack A',
            'description' => 'Primary lab router',
            'polling_interval' => 60,
            'status' => 'active',
        ];

        $this->actingAs($user)
            ->post(route('devices.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('devices', [
            'name' => 'Core Router',
            'ip_address' => '10.10.10.1',
            'vendor' => 'mikrotik',
        ]);
    }

    public function test_viewer_cannot_create_device(): void
    {
        Permission::findOrCreate('devices.view');
        Permission::findOrCreate('devices.create');

        $viewerRole = Role::findOrCreate('viewer');
        $viewerRole->syncPermissions(['devices.view']);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($viewerRole);

        $this->actingAs($user)
            ->post(route('devices.store'), [
                'name' => 'Denied Router',
                'vendor' => 'cisco',
                'ip_address' => '10.10.10.2',
                'snmp_version' => 'v2c',
                'community' => 'public',
                'port' => 161,
                'polling_interval' => 60,
                'status' => 'active',
            ])
            ->assertForbidden();
    }
}
