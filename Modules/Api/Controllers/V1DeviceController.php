<?php

namespace Modules\Api\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Devices\Dto\DeviceData;
use Modules\Devices\Models\Device;
use Modules\Devices\Requests\StoreDeviceRequest;
use Modules\Devices\Requests\UpdateDeviceRequest;
use Modules\Devices\Resources\DeviceResource;
use Modules\Devices\Services\DeviceService;

class V1DeviceController
{
    public function __construct(
        private readonly DeviceService $deviceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('devices.view'), 403);

        $devices = $this->deviceService->list($request->only([
            'search',
            'vendor',
            'status',
            'reachability',
            'snmp_version',
        ]), (int) $request->integer('per_page', 15));

        return DeviceResource::collection($devices)->response();
    }

    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['created_by'] = $request->user()->id;

        $device = $this->deviceService->create(DeviceData::fromArray($payload));

        return (new DeviceResource($device))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Device $device): DeviceResource
    {
        abort_unless($request->user()?->can('devices.view'), 403);

        return new DeviceResource($device);
    }

    public function update(UpdateDeviceRequest $request, Device $device): DeviceResource
    {
        $updated = $this->deviceService->update($device->id, DeviceData::fromArray($request->validated()));

        return new DeviceResource($updated);
    }

    public function destroy(Request $request, Device $device): JsonResponse
    {
        abort_unless($request->user()?->can('devices.delete'), 403);

        $this->deviceService->delete($device->id);

        return response()->json(['message' => 'Device deleted successfully.']);
    }
}
