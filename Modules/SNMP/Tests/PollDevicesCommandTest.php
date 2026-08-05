<?php

namespace Modules\SNMP\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Devices\Models\Device;
use Modules\SNMP\Dto\SnmpConnectionResult;
use Modules\SNMP\Dto\SnmpSystemInfo;
use Modules\SNMP\Jobs\PollDeviceJob;
use Modules\SNMP\Services\SNMPService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PollDevicesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_poll_command_queues_due_devices(): void
    {
        Queue::fake();

        Device::factory()->create([
            'status' => 'active',
            'polling_interval' => 60,
            'last_polled_at' => null,
        ]);

        $this->artisan('devices:poll')
            ->assertSuccessful();

        Queue::assertPushed(PollDeviceJob::class);
    }

    public function test_test_snmp_endpoint_returns_connection_payload(): void
    {
        Permission::findOrCreate('devices.view');
        $role = Role::findOrCreate('viewer');
        $role->givePermissionTo('devices.view');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        $device = Device::factory()->create();

        $this->mock(SNMPService::class, function ($mock): void {
            $mock->shouldReceive('testConnection')->once()->andReturn(
                new SnmpConnectionResult(
                    connected: true,
                    message: 'Connected',
                    system: new SnmpSystemInfo(
                        hostname: 'lab-router',
                        description: 'RouterOS',
                        uptime: '12345',
                        location: 'Lab',
                        contact: 'noc@example.test',
                    ),
                    interfacesCount: 4,
                )
            );
        });

        $this->actingAs($user)
            ->postJson(route('devices.test-snmp', $device))
            ->assertOk()
            ->assertJsonPath('connected', true)
            ->assertJsonPath('system.hostname', 'lab-router')
            ->assertJsonPath('interfaces_count', 4);
    }
}
