<x-monitor-layout
    title="Interfaces"
    header="Interfaces"
    subheader="Latest SNMP interface inventory from polled devices"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Interfaces'],
    ]"
>
    <div class="sgr-card mb-4 p-4">
        <form method="GET" action="{{ route('interfaces.index') }}" class="grid gap-3 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-4">
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="sgr-input" placeholder="Name, description, status">
            </div>
            <div class="lg:col-span-3">
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Device</label>
                <select name="device_id" class="sgr-input">
                    <option value="">All devices</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->id }}" @selected((string) ($filters['device_id'] ?? '') === (string) $device->id)>{{ $device->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Status</label>
                <select name="status" class="sgr-input">
                    <option value="">All</option>
                    @foreach(['up','down','testing','unknown','dormant','notPresent','lowerLayerDown'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ strtoupper($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 lg:col-span-3">
                <button class="sgr-btn-primary flex-1">Filter</button>
                <a href="{{ route('interfaces.index') }}" class="sgr-btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="sgr-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div class="text-sm font-semibold text-slate-900">{{ $interfaces->total() }} interfaces found</div>
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-700">Live</span>
        </div>
        <div class="overflow-x-auto">
            <table class="sgr-table min-w-full text-left">
                <thead class="bg-slate-50/80">
                <tr>
                    <th>Interface</th>
                    <th>Device</th>
                    <th>Status</th>
                    <th>Speed</th>
                    <th>RX Bytes</th>
                    <th>TX Bytes</th>
                    <th>Errors</th>
                    <th>Last Poll</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($interfaces as $interface)
                    <tr class="hover:bg-slate-50/70">
                        <td>
                            <div class="font-semibold text-slate-900">{{ $interface->name }}</div>
                            <div class="text-xs text-slate-400">{{ $interface->description ?: 'ifIndex '.$interface->if_index }}</div>
                        </td>
                        <td>
                            <a href="{{ route('devices.show', $interface->device_id) }}" class="font-medium text-cyan-700 hover:underline">
                                {{ $interface->device?->name }}
                            </a>
                        </td>
                        <td>
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold uppercase',
                                'bg-emerald-50 text-emerald-700' => $interface->oper_status === 'up',
                                'bg-rose-50 text-rose-700' => $interface->oper_status === 'down',
                                'bg-slate-100 text-slate-600' => ! in_array($interface->oper_status, ['up', 'down'], true),
                            ])>{{ $interface->oper_status }}</span>
                        </td>
                        <td>{{ $interface->speed ? number_format($interface->speed) : '—' }}</td>
                        <td>{{ number_format((float) $interface->rx_bytes) }}</td>
                        <td>{{ number_format((float) $interface->tx_bytes) }}</td>
                        <td>{{ $interface->errors }}</td>
                        <td>{{ $interface->last_polled_at?->diffForHumans() ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-slate-400">No interfaces yet. Poll devices to populate this table.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">{{ $interfaces->links() }}</div>
    </div>
</x-monitor-layout>
