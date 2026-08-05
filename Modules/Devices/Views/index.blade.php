<x-monitor-layout
    title="Devices"
    header="Device Inventory"
    subheader="Manage SNMP endpoints for lab testing"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Devices'],
    ]"
>
    <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs text-slate-500">Total</div>
            <div class="mt-1 text-2xl font-semibold">{{ $stats['total'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs text-slate-500">Active</div>
            <div class="mt-1 text-2xl font-semibold text-emerald-600">{{ $stats['active'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs text-slate-500">Online</div>
            <div class="mt-1 text-2xl font-semibold">{{ $stats['online'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs text-slate-500">Offline</div>
            <div class="mt-1 text-2xl font-semibold text-rose-600">{{ $stats['offline'] }}</div>
        </div>
    </div>

    <div class="mb-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900 lg:flex-row lg:items-end lg:justify-between">
        <form method="GET" action="{{ route('devices.index') }}" class="grid flex-1 gap-3 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, IP, hostname"
                       class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Vendor</label>
                <select name="vendor" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <option value="">All vendors</option>
                    @foreach($vendors as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['vendor'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Status</label>
                <select name="status" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <option value="">All statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900">Filter</button>
                <a href="{{ route('devices.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-700">Reset</a>
            </div>
        </form>

        @can('devices.create')
            <a href="{{ route('devices.create') }}" class="inline-flex items-center justify-center rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-500">
                Add Device
            </a>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950/40">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Endpoint</th>
                    <th class="px-4 py-3">Vendor</th>
                    <th class="px-4 py-3">SNMP</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Reachability</th>
                    <th class="px-4 py-3"></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($devices as $device)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $device->name }}</td>
                        <td class="px-4 py-3">{{ $device->displayEndpoint() }}</td>
                        <td class="px-4 py-3">{{ $device->vendor?->label() }}</td>
                        <td class="px-4 py-3">{{ $device->snmp_version?->label() }}</td>
                        <td class="px-4 py-3">{{ $device->status?->label() }}</td>
                        <td class="px-4 py-3">{{ $device->reachability?->label() }}</td>
                        <td class="px-4 py-3 text-end">
                            <a href="{{ route('devices.show', $device) }}" class="text-cyan-600 hover:underline dark:text-cyan-400">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-500">No devices match your filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
            {{ $devices->links() }}
        </div>
    </div>
</x-monitor-layout>
