<x-monitor-layout
    title="Dashboard"
    header="Operations Dashboard"
    subheader="Device health overview for SNMP lab validation"
    :breadcrumbs="[['label' => 'Dashboard']]"
>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs uppercase tracking-wide text-slate-500">Total Devices</div>
            <div class="mt-2 text-3xl font-semibold">{{ $stats['total_devices'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs uppercase tracking-wide text-slate-500">Online</div>
            <div class="mt-2 text-3xl font-semibold text-emerald-600">{{ $stats['online'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs uppercase tracking-wide text-slate-500">Offline</div>
            <div class="mt-2 text-3xl font-semibold text-rose-600">{{ $stats['offline'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs uppercase tracking-wide text-slate-500">Open Alerts</div>
            <div class="mt-2 text-3xl font-semibold text-amber-600">{{ $stats['open_alerts'] }}</div>
            <div class="mt-1 text-xs text-slate-400">{{ $placeholders['alerts'] }}</div>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-sm font-medium">CPU</div>
            <div class="mt-3 text-2xl font-semibold">{{ $stats['avg_cpu'] ?? '—' }}</div>
            <p class="mt-2 text-xs text-slate-500">{{ $placeholders['cpu'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-sm font-medium">Memory</div>
            <div class="mt-3 text-2xl font-semibold">{{ $stats['avg_memory'] ?? '—' }}</div>
            <p class="mt-2 text-xs text-slate-500">{{ $placeholders['memory'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <div class="text-sm font-medium">Temperature</div>
            <div class="mt-3 text-2xl font-semibold">{{ $stats['avg_temperature'] ?? '—' }}</div>
            <p class="mt-2 text-xs text-slate-500">{{ $placeholders['temperature'] }}</p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <div class="xl:col-span-2 rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <h2 class="font-medium">Recent Devices</h2>
                @can('devices.view')
                    <a href="{{ route('devices.index') }}" class="text-sm text-cyan-600 hover:underline dark:text-cyan-400">View all</a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950/50">
                    <tr>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">IP</th>
                        <th class="px-5 py-3">Vendor</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($recent_devices as $device)
                        <tr>
                            <td class="px-5 py-3">
                                <a href="{{ route('devices.show', $device) }}" class="font-medium text-cyan-700 hover:underline dark:text-cyan-400">{{ $device->name }}</a>
                            </td>
                            <td class="px-5 py-3">{{ $device->ip_address }}</td>
                            <td class="px-5 py-3">{{ $device->vendor?->label() }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium dark:bg-slate-800">
                                    {{ $device->status?->label() }} / {{ $device->reachability?->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-slate-500">No devices yet. Add your first SNMP endpoint to begin.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <h2 class="font-medium">System</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Polling</dt>
                    <dd>{{ $polling_enabled ? 'Enabled' : 'Disabled' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Bandwidth</dt>
                    <dd>{{ $stats['bandwidth_mbps'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Last Poll</dt>
                    <dd>{{ $stats['last_poll'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Active Devices</dt>
                    <dd>{{ $stats['active_devices'] }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-slate-500">{{ $placeholders['bandwidth'] }}</p>
        </div>
    </div>
</x-monitor-layout>
