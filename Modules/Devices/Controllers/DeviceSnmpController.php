<?php

namespace Modules\Devices\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Devices\Models\Device;
use Modules\Settings\Services\SnmpAgentClient;
use Modules\SNMP\Services\SNMPService;
use Throwable;

class DeviceSnmpController
{
    public function __construct(
        private readonly SNMPService $snmp,
        private readonly SnmpAgentClient $agent,
    ) {}

    public function test(Device $device): JsonResponse
    {
        abort_unless(auth()->user()?->can('devices.view'), 403);

        // Prefer on-prem Go agent when configured (polls from agent host, not the web server).
        if ($this->agent->configured()) {
            try {
                $result = $this->agent->testDevice($device);

                return response()->json([
                    'connected' => $result['connected'],
                    'message' => $result['message'],
                    'system' => $result['system'],
                    'interfaces_count' => $result['interfaces_count'],
                    'via' => 'snmp-agent',
                ], $result['connected'] ? 200 : 422);
            } catch (Throwable $e) {
                Log::warning('snmp-agent test failed; falling back to local PHP SNMP', [
                    'device_id' => $device->id,
                    'error' => $e->getMessage(),
                ]);

                // If agent is configured, do not silently succeed via wrong source IP.
                return response()->json([
                    'connected' => false,
                    'message' => 'snmp-agent test failed: '.$e->getMessage(),
                    'system' => null,
                    'interfaces_count' => 0,
                    'via' => 'snmp-agent',
                ], 422);
            }
        }

        $result = $this->snmp->testConnection($device);

        return response()->json([
            'connected' => $result->connected,
            'message' => $result->message,
            'system' => $result->system ? [
                'hostname' => $result->system->hostname,
                'description' => $result->system->description,
                'uptime' => $result->system->uptime,
                'location' => $result->system->location,
                'contact' => $result->system->contact,
            ] : null,
            'interfaces_count' => $result->interfacesCount,
            'via' => 'php-local',
        ], $result->connected ? 200 : 422);
    }
}
