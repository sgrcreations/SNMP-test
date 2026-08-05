<?php

namespace Modules\Devices\Controllers;

use App\Core\Enums\DeviceStatus;
use App\Core\Enums\DeviceVendor;
use App\Core\Enums\SnmpAuthProtocol;
use App\Core\Enums\SnmpPrivProtocol;
use App\Core\Enums\SnmpVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Devices\Dto\DeviceData;
use Modules\Devices\Models\Device;
use Modules\Devices\Requests\StoreDeviceRequest;
use Modules\Devices\Requests\UpdateDeviceRequest;
use Modules\Devices\Services\DeviceService;

class DeviceController
{
    public function __construct(
        private readonly DeviceService $deviceService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeViewAny();

        $devices = $this->deviceService->list($request->only([
            'search',
            'vendor',
            'status',
            'reachability',
            'snmp_version',
        ]), (int) $request->integer('per_page', 15));

        return view('devices::index', [
            'devices' => $devices,
            'filters' => $request->only(['search', 'vendor', 'status', 'reachability', 'snmp_version']),
            'vendors' => DeviceVendor::options(),
            'statuses' => DeviceStatus::options(),
            'snmpVersions' => SnmpVersion::options(),
            'stats' => $this->deviceService->stats(),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('devices.create'), 403);

        return view('devices::create', $this->formOptions());
    }

    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['created_by'] = $request->user()->id;

        $device = $this->deviceService->create(DeviceData::fromArray($payload));

        return redirect()
            ->route('devices.show', $device)
            ->with('success', 'Device created successfully.');
    }

    public function show(Device $device): View
    {
        abort_unless(auth()->user()?->can('devices.view'), 403);

        return view('devices::show', [
            'device' => $device,
        ]);
    }

    public function edit(Device $device): View
    {
        abort_unless(auth()->user()?->can('devices.update'), 403);

        return view('devices::edit', array_merge($this->formOptions(), [
            'device' => $device,
        ]));
    }

    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        $this->deviceService->update($device->id, DeviceData::fromArray($request->validated()));

        return redirect()
            ->route('devices.show', $device)
            ->with('success', 'Device updated successfully.');
    }

    public function destroy(Device $device): RedirectResponse
    {
        abort_unless(auth()->user()?->can('devices.delete'), 403);

        $this->deviceService->delete($device->id);

        return redirect()
            ->route('devices.index')
            ->with('success', 'Device deleted successfully.');
    }

    private function authorizeViewAny(): void
    {
        abort_unless(auth()->user()?->can('devices.view'), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'vendors' => DeviceVendor::options(),
            'snmpVersions' => SnmpVersion::options(),
            'authProtocols' => SnmpAuthProtocol::options(),
            'privProtocols' => SnmpPrivProtocol::options(),
            'statuses' => DeviceStatus::options(),
        ];
    }
}
