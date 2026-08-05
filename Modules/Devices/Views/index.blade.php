<x-monitor-layout
    title="Devices"
    header="Devices"
    subheader="Inventory of SNMP endpoints under test"
    :meta="$stats['total'].' devices · '.$stats['online'].' online'"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Devices'],
    ]"
>
    <x-slot:actions>
        <button type="button" onclick="window.location.reload()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-800" title="Refresh">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
        </button>
        @can('devices.create')
            <a href="{{ route('devices.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-cyan-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                Add Device
            </a>
        @endcan
    </x-slot:actions>

    <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="sgr-card p-4">
            <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Total</div>
            <div class="mt-2 text-2xl font-bold">{{ $stats['total'] }}</div>
        </div>
        <div class="sgr-card p-4">
            <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Active</div>
            <div class="mt-2 text-2xl font-bold text-emerald-600">{{ $stats['active'] }}</div>
        </div>
        <div class="sgr-card p-4">
            <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Online</div>
            <div class="mt-2 text-2xl font-bold text-cyan-600">{{ $stats['online'] }}</div>
        </div>
        <div class="sgr-card p-4">
            <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Offline</div>
            <div class="mt-2 text-2xl font-bold text-rose-600">{{ $stats['offline'] }}</div>
        </div>
    </div>

    <div class="sgr-card mb-4 p-4">
        <form method="GET" action="{{ route('devices.index') }}" class="grid gap-3 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-4">
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Search</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, IP, hostname" class="sgr-input ps-10">
                </div>
            </div>
            <div class="lg:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Vendor</label>
                <select name="vendor" class="sgr-input">
                    <option value="">All vendors</option>
                    @foreach($vendors as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['vendor'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Status</label>
                <select name="status" class="sgr-input">
                    <option value="">All statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 lg:col-span-4">
                <button class="sgr-btn-primary flex-1">Apply Filters</button>
                <a href="{{ route('devices.index') }}" class="sgr-btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="sgr-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <div class="text-sm font-semibold text-slate-900">{{ $devices->total() }} devices found</div>
                <div class="text-xs text-slate-400">Filtered inventory results</div>
            </div>
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-700">Live</span>
        </div>
        <div class="overflow-x-auto">
            <table class="sgr-table min-w-full text-left">
                <thead class="bg-slate-50/80">
                <tr>
                    <th>Device</th>
                    <th>Endpoint</th>
                    <th>Vendor</th>
                    <th>SNMP</th>
                    <th>Status</th>
                    <th>Reachability</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($devices as $device)
                    <tr class="hover:bg-slate-50/70">
                        <td>
                            <div class="font-semibold text-slate-900">{{ $device->name }}</div>
                            <div class="text-xs text-slate-400">{{ $device->model ?: 'No model' }}</div>
                        </td>
                        <td class="font-medium">{{ $device->displayEndpoint() }}</td>
                        <td>{{ $device->vendor?->label() }}</td>
                        <td>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                {{ $device->snmp_version?->label() }}
                            </span>
                        </td>
                        <td>{{ $device->status?->label() }}</td>
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
                        <td>
                            <a href="{{ route('devices.show', $device) }}" class="sgr-btn-icon" title="View details">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14 text-center text-slate-400">No devices match your filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $devices->links() }}
        </div>
    </div>
</x-monitor-layout>
