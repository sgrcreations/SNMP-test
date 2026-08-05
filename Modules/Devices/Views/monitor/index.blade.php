<x-monitor-layout
    title="Devices"
    header="Devices"
    status="Live"
    subheader="Switch, router and OLT monitoring from live SNMP polls"
    :breadcrumbs="[['label' => 'Devices']]"
>
    <x-slot:actions>
        <form method="POST" action="{{ route('devices.sync-all') }}">
            @csrf
            <button class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
                Sync
            </button>
        </form>
        @can('devices.create')
            <a href="{{ route('devices.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-cyan-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-cyan-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                Add
            </a>
        @endcan
    </x-slot:actions>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        @foreach([
            ['Total', $stats['total'], 'text-slate-900', 'bg-slate-100'],
            ['Online', $stats['online'], 'text-emerald-600', 'bg-emerald-50'],
            ['Offline', $stats['offline'], 'text-rose-600', 'bg-rose-50'],
            ['Routers', $stats['routers'], 'text-violet-600', 'bg-violet-50'],
            ['Switches', $stats['switches'], 'text-amber-600', 'bg-amber-50'],
            ['OLTs', $stats['olts'], 'text-cyan-600', 'bg-cyan-50'],
        ] as [$label, $value, $color, $bg])
            <div class="sgr-card p-4">
                <div class="flex items-center justify-between">
                    <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ $label }}</div>
                    <span class="rounded-lg {{ $bg }} px-2 py-1 text-xs {{ $color }}">●</span>
                </div>
                <div class="mt-2 text-3xl font-bold {{ $color }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="sgr-card mb-4 p-4">
        <form method="GET" class="grid gap-3 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-4">
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Find device..." class="sgr-input">
            </div>
            <div class="lg:col-span-2">
                <select name="device_type" class="sgr-input">
                    <option value="">Type: All</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['device_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <select name="status" class="sgr-input">
                    <option value="">Status: All</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="lg:col-span-2">
                <select name="reachability" class="sgr-input">
                    <option value="">Reachability</option>
                    <option value="online" @selected(($filters['reachability'] ?? '') === 'online')>Online</option>
                    <option value="offline" @selected(($filters['reachability'] ?? '') === 'offline')>Offline</option>
                    <option value="unknown" @selected(($filters['reachability'] ?? '') === 'unknown')>Unknown</option>
                </select>
            </div>
            <div class="flex gap-2 lg:col-span-2">
                <button class="sgr-btn-primary flex-1">Filter</button>
                <a href="{{ route('devices.map') }}" class="sgr-btn-secondary">Map</a>
            </div>
        </form>
    </div>

    <div class="sgr-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div class="text-sm font-semibold">{{ $devices->total() }} devices found</div>
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-700">Live</span>
        </div>
        <div class="overflow-x-auto">
            <table class="sgr-table min-w-full text-left">
                <thead class="bg-slate-50/80">
                <tr>
                    <th></th>
                    <th>Device</th>
                    <th>Type</th>
                    <th>Hostname</th>
                    <th>Area</th>
                    <th>IP Address</th>
                    <th>Specs</th>
                    <th>CPU</th>
                    <th>Last Seen</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($devices as $device)
                    @php $reach = $device->reachability?->value; @endphp
                    <tr class="hover:bg-slate-50/80">
                        <td>
                            <span @class([
                                'inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                                'bg-emerald-50 text-emerald-600' => $reach === 'online',
                                'bg-rose-50 text-rose-600' => $reach === 'offline',
                                'bg-slate-100 text-slate-500' => ! in_array($reach, ['online','offline'], true),
                            ])>{{ $reach === 'online' ? '✓' : ($reach === 'offline' ? '!' : '?') }}</span>
                        </td>
                        <td>
                            <a href="{{ route('devices.show', $device) }}" class="font-semibold text-slate-900 hover:text-cyan-700">{{ $device->name }}</a>
                            <div class="text-xs text-slate-400">{{ $device->model ?: 'No model' }}</div>
                        </td>
                        <td>
                            <span @class([
                                'rounded-full px-2.5 py-1 text-[10px] font-bold uppercase',
                                'bg-violet-50 text-violet-700' => $device->device_type?->value === 'router',
                                'bg-amber-50 text-amber-700' => $device->device_type?->value === 'switch',
                                'bg-cyan-50 text-cyan-700' => $device->device_type?->value === 'olt',
                                'bg-slate-100 text-slate-600' => ! in_array($device->device_type?->value, ['router','switch','olt'], true),
                            ])>{{ $device->device_type?->label() }}</span>
                        </td>
                        <td>{{ $device->hostname ?: '—' }}</td>
                        <td>{{ $device->area ?: ($device->location ?: '—') }}</td>
                        <td><a href="{{ route('devices.show', $device) }}" class="font-medium text-cyan-700 hover:underline">{{ $device->ip_address }}</a></td>
                        <td class="text-xs">{{ strtoupper($device->vendor?->value) }} {{ $device->model }}</td>
                        <td>{{ $device->last_cpu !== null ? $device->last_cpu.'%' : '—' }}</td>
                        <td>{{ $device->last_seen_at?->diffForHumans(short: true) ?: 'Never' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-12 text-center text-slate-400">No devices found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">{{ $devices->links() }}</div>
    </div>
</x-monitor-layout>
