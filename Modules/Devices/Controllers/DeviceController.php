<?php

namespace Modules\Devices\Controllers;

use App\Core\Enums\DeviceStatus;
use App\Core\Enums\DeviceType;
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
use Modules\Settings\Services\SettingService;

class DeviceController
{
    public function __construct(
        private readonly DeviceService $deviceService,
        private readonly SettingService $settings,
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

        return view('devices::create', $this->formOptions(null));
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

        $device->load(['interfaces' => fn ($query) => $query->orderBy('if_index')->limit(20)]);

        return view('devices::show', [
            'device' => $device,
            'latestMetric' => $device->metrics()->latest('recorded_at')->first(),
        ]);
    }

    public function edit(Device $device): View
    {
        abort_unless(auth()->user()?->can('devices.update'), 403);

        return view('devices::edit', array_merge($this->formOptions($device), [
            'device' => $device,
        ]));
    }

    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        $updated = $this->deviceService->update($device->id, DeviceData::fromArray($request->validated()));

        $redirect = redirect()
            ->route('devices.show', $updated)
            ->with('success', 'Device updated successfully.');

        if ($updated->isOlt()) {
            return redirect()
                ->route('devices.show', ['device' => $updated, 'tab' => 'command-center'])
                ->with('success', 'Device updated. OLT workspace unlocked (Command Center, PON, ONUs, Tree).');
        }

        return $redirect;
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
    private function formOptions(?Device $device = null): array
    {
        $defaultInterval = max(30, (int) $this->settings->get('default_polling_interval', 60));

        return [
            'vendors' => DeviceVendor::options(),
            'deviceTypes' => DeviceType::options(),
            'snmpVersions' => SnmpVersion::options(),
            'authProtocols' => SnmpAuthProtocol::options(),
            'privProtocols' => SnmpPrivProtocol::options(),
            'statuses' => DeviceStatus::options(),
            'defaultPollingInterval' => $defaultInterval,
            'device' => $device,
        ];
    }
}
