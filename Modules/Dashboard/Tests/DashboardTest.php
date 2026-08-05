<?php

namespace Modules\Dashboard\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_with_permission_can_view_dashboard(): void
    {
        Permission::findOrCreate('dashboard.view');
        $role = Role::findOrCreate('viewer');
        $role->givePermissionTo('dashboard.view');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Command Center');
    }
}
