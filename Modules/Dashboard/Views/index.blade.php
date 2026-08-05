<x-monitor-layout
    title="Dashboard"
    header="Command Center"
    subheader="Live overview of your SNMP lab endpoints"
    status="Online"
    :breadcrumbs="[['label' => 'Dashboard']]"
>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="sgr-card p-5">
            <div class="flex items-center justify-between">
                <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Total Devices</div>
                <span class="rounded-xl bg-cyan-50 p-2 text-cyan-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h10"/></svg>
                </span>
            </div>
            <div class="mt-3 text-3xl font-bold tracking-tight">{{ $stats['total_devices'] }}</div>
            <p class="mt-1 text-xs text-slate-400">{{ $stats['active_devices'] }} active for polling</p>
        </div>

        <div class="sgr-card p-5">
            <div class="flex items-center justify-between">
                <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Online</div>
                <span class="rounded-xl bg-emerald-50 p-2 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="mt-3 text-3xl font-bold tracking-tight text-emerald-600">{{ $stats['online'] }}</div>
            <p class="mt-1 text-xs text-slate-400">Reachable endpoints</p>
        </div>

        <div class="sgr-card p-5">
            <div class="flex items-center justify-between">
                <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Offline</div>
                <span class="rounded-xl bg-rose-50 p-2 text-rose-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </span>
            </div>
            <div class="mt-3 text-3xl font-bold tracking-tight text-rose-600">{{ $stats['offline'] }}</div>
            <p class="mt-1 text-xs text-slate-400">Need attention</p>
        </div>

        <div class="sgr-card p-5">
            <div class="flex items-center justify-between">
                <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Open Alerts</div>
                <span class="rounded-xl bg-amber-50 p-2 text-amber-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1h6z"/></svg>
                </span>
            </div>
            <div class="mt-3 text-3xl font-bold tracking-tight text-amber-600">{{ $stats['open_alerts'] }}</div>
            <p class="mt-1 text-xs text-slate-400">Threshold and reachability events</p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="sgr-card p-5">
            <div class="text-sm font-semibold text-slate-800">CPU</div>
            <div class="mt-3 text-3xl font-bold">{{ $stats['avg_cpu'] ?? '—' }}</div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full w-0 rounded-full bg-cyan-500"></div>
            </div>
            <p class="mt-2 text-xs text-slate-400">Average across polled devices</p>
        </div>
        <div class="sgr-card p-5">
            <div class="text-sm font-semibold text-slate-800">Memory</div>
            <div class="mt-3 text-3xl font-bold">{{ $stats['avg_memory'] ?? '—' }}</div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full w-0 rounded-full bg-cyan-500"></div>
            </div>
            <p class="mt-2 text-xs text-slate-400">Average utilization</p>
        </div>
        <div class="sgr-card p-5">
            <div class="text-sm font-semibold text-slate-800">Temperature</div>
            <div class="mt-3 text-3xl font-bold">{{ $stats['avg_temperature'] ?? '—' }}</div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full w-0 rounded-full bg-amber-400"></div>
            </div>
            <p class="mt-2 text-xs text-slate-400">Average sensor reading</p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <div class="sgr-card xl:col-span-2 overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-slate-900">Recent Devices</h2>
                    <p class="text-xs text-slate-400">Latest inventory entries</p>
                </div>
                @can('devices.view')
                    <a href="{{ route('devices.index') }}" class="text-sm font-semibold text-cyan-600 hover:text-cyan-700">View all</a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="sgr-table min-w-full text-left">
                    <thead class="bg-slate-50/80">
                    <tr>
                        <th>Device</th>
                        <th>IP Address</th>
                        <th>Vendor</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($recent_devices as $device)
                        <tr class="hover:bg-slate-50/70">
                            <td>
                                <div class="font-semibold text-slate-900">{{ $device->name }}</div>
                                <div class="text-xs text-slate-400">{{ $device->hostname ?: 'No hostname' }}</div>
                            </td>
                            <td class="font-medium text-slate-600">{{ $device->ip_address }}</td>
                            <td>{{ $device->vendor?->label() }}</td>
                            <td>
                                @php $reach = $device->reachability?->value; @endphp
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide',
                                    'bg-emerald-50 text-emerald-700' => $reach === 'online',
                                    'bg-rose-50 text-rose-700' => $reach === 'offline',
                                    'bg-slate-100 text-slate-600' => ! in_array($reach, ['online', 'offline'], true),
                                ])>
                                    <span @class([
                                        'h-1.5 w-1.5 rounded-full',
                                        'bg-emerald-500' => $reach === 'online',
                                        'bg-rose-500' => $reach === 'offline',
                                        'bg-slate-400' => ! in_array($reach, ['online', 'offline'], true),
                                    ])></span>
                                    {{ $device->reachability?->label() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('devices.show', $device) }}" class="sgr-btn-icon" title="View">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">No devices yet. Add your first SNMP endpoint to begin.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sgr-card p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">System Health</h2>
                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-700">Live</span>
            </div>
            <dl class="mt-5 space-y-4 text-sm">
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-slate-500">Polling</dt>
                    <dd class="font-semibold {{ $polling_enabled ? 'text-emerald-600' : 'text-slate-700' }}">{{ $polling_enabled ? 'Enabled' : 'Disabled' }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-slate-500">Bandwidth</dt>
                    <dd class="font-semibold">{{ $stats['bandwidth_mbps'] ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-slate-500">Last Poll</dt>
                    <dd class="font-semibold">{{ $stats['last_poll'] ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-slate-500">Unknown State</dt>
                    <dd class="font-semibold">{{ $stats['unknown'] }}</dd>
                </div>
            </dl>
            @can('devices.create')
                <a href="{{ route('devices.create') }}" class="sgr-btn-primary mt-5 w-full">Add Device</a>
            @endcan
        </div>
    </div>
</x-monitor-layout>
