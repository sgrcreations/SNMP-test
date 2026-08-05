<?php

namespace Modules\Api\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Devices\Models\Device;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        foreach (['devices.view', 'devices.create', 'dashboard.view', 'api.access'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $role = Role::findOrCreate('admin');
        $role->syncPermissions(Permission::all());

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        return $user;
    }

    public function test_api_login_returns_token(): void
    {
        $user = $this->adminUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'phpunit',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email']]);
    }

    public function test_api_devices_index_requires_auth(): void
    {
        $this->getJson('/api/v1/devices')->assertUnauthorized();
    }

    public function test_api_devices_index_returns_collection(): void
    {
        $user = $this->adminUser();
        Device::factory()->count(3)->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_api_dashboard_returns_stats(): void
    {
        $user = $this->adminUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.total_devices', 0);
    }
}
