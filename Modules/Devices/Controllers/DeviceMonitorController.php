<?php

namespace Modules\Devices\Controllers;

use App\Core\Enums\DeviceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Alerts\Models\Alert;
use Modules\Devices\Models\Device;
use Modules\Devices\Services\DeviceMonitorService;
use Modules\Devices\Services\DeviceService;
use Modules\SNMP\Jobs\PollDeviceJob;
use Modules\SNMP\Services\DevicePollService;

class DeviceMonitorController
{
    public function __construct(
        private readonly DeviceService $deviceService,
        private readonly DeviceMonitorService $monitor,
        private readonly DevicePollService $poller,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('devices.view'), 403);

        $devices = $this->deviceService->list($request->only([
            'search', 'vendor', 'status', 'reachability', 'snmp_version',
        ]) + array_filter([
            'device_type' => $request->string('device_type')->toString() ?: null,
        ]), (int) $request->integer('per_page', 50));

        return view('devices::monitor.index', [
            'devices' => $devices,
            'stats' => $this->monitor->inventoryStats(),
            'filters' => $request->only(['search', 'vendor', 'status', 'reachability', 'device_type']),
            'types' => DeviceType::options(),
        ]);
    }

    public function show(Request $request, Device $device): View
    {
        abort_unless($request->user()?->can('devices.view'), 403);

        $tab = $request->string('tab', $device->isOlt() ? 'command-center' : 'overview')->toString();
        $range = $request->string('range', '24h')->toString();

        $device->load([
            'interfaces' => fn ($q) => $q->orderBy('if_index'),
            'onus' => fn ($q) => $q->orderBy('pon_port')->orderBy('onu_id'),
        ]);

        return view('devices::monitor.show', [
            'device' => $device,
            'tab' => $tab,
            'range' => $range,
            'overview' => $this->monitor->overview($device),
            'pollingProfile' => $this->monitor->pollingProfile($device),
            'metricSeries' => $this->monitor->metricSeries($device, $range),
            'trafficSeries' => $this->monitor->uplinkTrafficSeries($device, $range),
            'qualitySeries' => $this->monitor->qualitySeries($device, $range),
            'onuAvailabilitySeries' => $this->monitor->onuAvailabilitySeries($device, $range),
            'statusEvents' => $this->monitor->statusTimeline($device),
            'vlans' => $device->vlans()->orderBy('vlan_id')->get(),
            'pollLogs' => $device->pollLogs()->latest('started_at')->limit(30)->get(),
            'alerts' => $device->alerts()->latest('raised_at')->limit(50)->get(),
            'openAlerts' => $device->alerts()->where('status', 'open')->latest('raised_at')->get(),
            'ponPorts' => $device->interfaces->where('port_role', 'pon')->values(),
            'accessPorts' => $device->interfaces->whereIn('port_role', ['access', 'uplink'])->values(),
            'onus' => $device->onus,
        ]);
    }

    public function poll(Request $request, Device $device): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()?->can('devices.update') || $request->user()?->can('devices.view'), 403);

        $sync = $request->boolean('sync', true);
        if ($sync) {
            $result = $this->poller->poll($device);
        } else {
            PollDeviceJob::dispatch($device->id);
            $result = ['success' => true, 'message' => 'Poll queued'];
        }

        if ($request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? 'Device synced successfully.' : ('Sync failed: '.$result['message'])
        );
    }

    public function syncAll(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('devices.view'), 403);

        Device::query()->where('status', 'active')->each(function (Device $device): void {
            PollDeviceJob::dispatch($device->id);
        });

        return back()->with('success', 'Sync queued for all active devices.');
    }

    public function metrics(Request $request, Device $device): JsonResponse
    {
        abort_unless($request->user()?->can('devices.view'), 403);

        $range = $request->string('range', '24h')->toString();

        return response()->json([
            'metrics' => $this->monitor->metricSeries($device, $range),
            'traffic' => $this->monitor->uplinkTrafficSeries($device, $range),
            'overview' => [
                'cpu' => $device->fresh()->last_cpu,
                'memory' => $device->fresh()->last_memory,
                'temperature' => $device->fresh()->last_temperature,
            ],
            'polling' => $this->monitor->pollingProfile($device),
        ]);
    }

    public function toggleUplink(Request $request, Device $device, int $interface): RedirectResponse
    {
        abort_unless($request->user()?->can('devices.update'), 403);

        $iface = $device->interfaces()->whereKey($interface)->firstOrFail();
        $iface->update([
            'is_uplink' => ! $iface->is_uplink,
            'port_role' => ! $iface->is_uplink ? 'uplink' : ($iface->port_role === 'pon' ? 'pon' : 'access'),
        ]);

        return back()->with('success', $iface->is_uplink ? 'Marked as uplink.' : 'Uplink flag removed.');
    }

    public function map(Request $request): View
    {
        abort_unless($request->user()?->can('devices.view'), 403);

        $map = $this->monitor->mapDevices();

        return view('devices::monitor.map', [
            'mapped' => $map['mapped'],
            'unmapped' => $map['unmapped'],
            'stats' => $this->monitor->inventoryStats(),
            'alerts' => Alert::query()->where('status', 'open')->with('device')->latest('raised_at')->limit(20)->get(),
        ]);
    }
}
