<?php

namespace Modules\Alerts\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Alerts\Models\Alert;
use Modules\Devices\Models\Device;

class AlertController
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('devices.view') || $request->user()?->can('dashboard.view'), 403);

        $query = Alert::query()
            ->with(['device:id,name,ip_address', 'networkInterface:id,name'])
            ->latest('raised_at');

        $status = $request->string('status')->toString() ?: 'open';
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($severity = $request->string('severity')->toString()) {
            $query->where('severity', $severity);
        }

        if ($deviceId = $request->integer('device_id')) {
            $query->where('device_id', $deviceId);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        return view('alerts::index', [
            'alerts' => $query->paginate(40)->withQueryString(),
            'devices' => Device::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $status,
                'severity' => $request->string('severity')->toString(),
                'device_id' => $request->integer('device_id') ?: '',
            ],
            'counts' => [
                'open' => Alert::query()->where('status', 'open')->count(),
                'all' => Alert::query()->count(),
            ],
        ]);
    }
}
