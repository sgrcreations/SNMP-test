<?php

namespace Modules\Devices\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Devices\Models\Device;
use Modules\SNMP\Services\SNMPService;

class DeviceSnmpController
{
    public function __construct(
        private readonly SNMPService $snmp,
    ) {}

    public function test(Device $device): JsonResponse
    {
        abort_unless(auth()->user()?->can('devices.view'), 403);

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
        ], $result->connected ? 200 : 422);
    }
}
