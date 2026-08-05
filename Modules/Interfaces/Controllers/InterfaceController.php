<?php

namespace Modules\Interfaces\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Devices\Models\Device;
use Modules\Interfaces\Models\DeviceInterface;

class InterfaceController
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('devices.view'), 403);

        $query = DeviceInterface::query()->with('device')->latest('last_polled_at');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('oper_status', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('oper_status', $status);
        }

        if ($request->boolean('uplink')) {
            $query->where('is_uplink', true);
        }

        if ($deviceId = $request->integer('device_id')) {
            $query->where('device_id', $deviceId);
        }

        return view('interfaces::index', [
            'interfaces' => $query->paginate(25)->withQueryString(),
            'devices' => Device::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'device_id', 'uplink']),
        ]);
    }
}