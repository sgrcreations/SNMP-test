<x-monitor-layout
    title="Alerts"
    header="Operational Alerts"
    subheader="All threshold and reachability alerts from polled devices"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Alerts'],
    ]"
>
    <div class="mb-4 grid gap-3 sm:grid-cols-2">
        <div class="sgr-card p-4">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Open</div>
            <div class="mt-2 text-2xl font-bold text-amber-600">{{ $counts['open'] }}</div>
        </div>
        <div class="sgr-card p-4">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">All time</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $counts['all'] }}</div>
        </div>
    </div>

    <div class="sgr-card mb-4 p-4">
        <form method="GET" action="{{ route('alerts.index') }}" class="grid gap-3 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-3">
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] }}" class="sgr-input" placeholder="Title, message, type">
            </div>
            <div class="lg:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Status</label>
                <select name="status" class="sgr-input">
                    @foreach(['open' => 'Open', 'acknowledged' => 'Acknowledged', 'resolved' => 'Resolved', 'all' => 'All'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Severity</label>
                <select name="severity" class="sgr-input">
                    <option value="">All</option>
                    @foreach(['critical','warning','info'] as $sev)
                        <option value="{{ $sev }}" @selected(($filters['severity'] ?? '') === $sev)>{{ ucfirst($sev) }}</option>
                    @endforeach
                </select>
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
            <div class="flex gap-2 lg:col-span-2">
                <button class="sgr-btn-primary flex-1">Filter</button>
                <a href="{{ route('alerts.index') }}" class="sgr-btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="sgr-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="sgr-table min-w-full text-left">
                <thead class="bg-slate-50/80">
                <tr>
                    <th>Alert</th>
                    <th>Device</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Raised</th>
                    <th></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($alerts as $alert)
                    <tr class="hover:bg-slate-50/70">
                        <td>
                            <div class="font-semibold text-slate-900">{{ $alert->title }}</div>
                            <div class="max-w-md truncate text-xs text-slate-400">{{ $alert->message ?: $alert->type }}</div>
                        </td>
                        <td>
                            @if($alert->device)
                                <a href="{{ route('devices.show', ['device' => $alert->device, 'tab' => 'alarms']) }}" class="font-medium text-cyan-700 hover:underline">
                                    {{ $alert->device->name }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold uppercase',
                                'bg-rose-50 text-rose-700' => $alert->severity === 'critical',
                                'bg-amber-50 text-amber-700' => $alert->severity === 'warning',
                                'bg-slate-100 text-slate-600' => ! in_array($alert->severity, ['critical', 'warning'], true),
                            ])>{{ $alert->severity }}</span>
                        </td>
                        <td class="text-sm font-semibold uppercase text-slate-600">{{ $alert->status }}</td>
                        <td class="text-sm text-slate-500">{{ $alert->raised_at?->diffForHumans() ?: '—' }}</td>
                        <td class="text-end">
                            @if($alert->device)
                                <a href="{{ route('devices.show', ['device' => $alert->device, 'tab' => 'alarms']) }}" class="text-sm font-semibold text-cyan-600">Open</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-slate-400">No alerts match these filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">{{ $alerts->links() }}</div>
    </div>
</x-monitor-layout>
