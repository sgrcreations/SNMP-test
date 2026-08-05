<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'devices.view',
            'devices.create',
            'devices.update',
            'devices.delete',
            'settings.view',
            'settings.update',
            'alerts.view',
            'alerts.acknowledge',
            'alerts.resolve',
            'reports.view',
            'api.access',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $admin = Role::findOrCreate('admin');
        $operator = Role::findOrCreate('operator');
        $viewer = Role::findOrCreate('viewer');

        $admin->syncPermissions($permissions);
        $operator->syncPermissions([
            'dashboard.view',
            'devices.view',
            'devices.create',
            'devices.update',
            'settings.view',
            'alerts.view',
            'alerts.acknowledge',
            'alerts.resolve',
            'reports.view',
            'api.access',
        ]);
        $viewer->syncPermissions([
            'dashboard.view',
            'devices.view',
            'alerts.view',
            'reports.view',
        ]);

        $adminUser = User::query()->updateOrCreate(
            ['email' => 'admin@sgrcreations.test'],
            [
                'name' => 'SNMP Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->syncRoles(['admin']);

        $operatorUser = User::query()->updateOrCreate(
            ['email' => 'operator@snmpmonitor.test'],
            [
                'name' => 'SNMP Operator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $operatorUser->syncRoles(['operator']);

        $defaults = [
            ['group' => 'polling', 'key' => 'polling_enabled', 'value' => '1', 'type' => 'boolean', 'label' => 'Polling Enabled', 'description' => 'Master switch for scheduled SNMP polling.'],
            ['group' => 'polling', 'key' => 'default_polling_interval', 'value' => '60', 'type' => 'integer', 'label' => 'Default Polling Interval', 'description' => 'Default interval in seconds for new devices.'],
            ['group' => 'snmp', 'key' => 'snmp_timeout', 'value' => '3', 'type' => 'integer', 'label' => 'SNMP Timeout', 'description' => 'Timeout in seconds for SNMP operations.'],
            ['group' => 'snmp', 'key' => 'snmp_retries', 'value' => '1', 'type' => 'integer', 'label' => 'SNMP Retries', 'description' => 'Number of retries for failed SNMP requests.'],
            ['group' => 'alerts', 'key' => 'cpu_threshold', 'value' => '85', 'type' => 'integer', 'label' => 'CPU Threshold (%)', 'description' => 'Raise an alert when CPU exceeds this percent.'],
            ['group' => 'alerts', 'key' => 'memory_threshold', 'value' => '90', 'type' => 'integer', 'label' => 'Memory Threshold (%)', 'description' => 'Raise an alert when memory exceeds this percent.'],
            ['group' => 'alerts', 'key' => 'temperature_threshold', 'value' => '70', 'type' => 'integer', 'label' => 'Temperature Threshold (°C)', 'description' => 'Raise an alert when temperature exceeds this value.'],
            ['group' => 'alerts', 'key' => 'bandwidth_threshold', 'value' => '80', 'type' => 'integer', 'label' => 'Bandwidth Threshold (%)', 'description' => 'Raise an alert when interface utilization exceeds this percent.'],
            ['group' => 'general', 'key' => 'app_timezone', 'value' => 'UTC', 'type' => 'string', 'label' => 'Application Timezone', 'description' => 'Timezone used for charts and scheduling display.'],
            ['group' => 'general', 'key' => 'items_per_page', 'value' => '15', 'type' => 'integer', 'label' => 'Items Per Page', 'description' => 'Default pagination size for tables.'],
            ['group' => 'agent', 'key' => 'snmp_agent_url', 'value' => 'http://127.0.0.1:9080', 'type' => 'string', 'label' => 'SNMP Agent URL', 'description' => 'Base URL of the on-prem Go snmp-agent (e.g. http://10.0.0.50:9080).'],
            ['group' => 'agent', 'key' => 'snmp_agent_api_key', 'value' => '', 'type' => 'string', 'label' => 'SNMP Agent API Key', 'description' => 'Shared API key matching the agent config api_key. Used server-side only.', 'is_encrypted' => true],
        ];

        foreach ($defaults as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'label' => $setting['label'],
                    'description' => $setting['description'],
                    'is_encrypted' => (bool) ($setting['is_encrypted'] ?? false),
                ]
            );
        }
    }
}
