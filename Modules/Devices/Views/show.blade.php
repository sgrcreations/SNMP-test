<x-monitor-layout
    title="{{ $device->name }}"
    header="{{ $device->name }}"
    subheader="{{ $device->displayEndpoint() }}"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Devices', 'url' => route('devices.index')],
        ['label' => $device->name],
    ]"
>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2 text-sm">
            <span class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-800">{{ $device->vendor?->label() }}</span>
            <span class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-800">{{ $device->snmp_version?->label() }}</span>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">{{ $device->status?->label() }}</span>
            <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">{{ $device->reachability?->label() }}</span>
        </div>
        <div class="flex gap-2">
            <button type="button" disabled class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-400 dark:border-slate-700" title="Available in Phase 2">
                Test SNMP
            </button>
            @can('devices.update')
                <a href="{{ route('devices.edit', $device) }}" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-500">Edit</a>
            @endcan
            @can('devices.delete')
                <form method="POST" action="{{ route('devices.destroy', $device) }}" onsubmit="return confirm('Delete this device?')">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg border border-rose-300 px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:border-rose-800 dark:hover:bg-rose-950/30">Delete</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Device Details</h2>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Hostname</dt><dd class="font-medium">{{ $device->hostname ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Model</dt><dd class="font-medium">{{ $device->model ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Location</dt><dd class="font-medium">{{ $device->location ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Polling Interval</dt><dd class="font-medium">{{ $device->polling_interval }}s</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500">Description</dt><dd class="font-medium">{{ $device->description ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">SNMP Profile</h2>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Version</dt><dd class="font-medium">{{ $device->snmp_version?->label() }}</dd></div>
                <div><dt class="text-slate-500">Port</dt><dd class="font-medium">{{ $device->port }}</dd></div>
                <div><dt class="text-slate-500">Username</dt><dd class="font-medium">{{ $device->username ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Auth Protocol</dt><dd class="font-medium">{{ $device->auth_protocol?->label() ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Privacy Protocol</dt><dd class="font-medium">{{ $device->priv_protocol?->label() ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Credentials</dt><dd class="font-medium">Encrypted at rest</dd></div>
                <div><dt class="text-slate-500">Last Polled</dt><dd class="font-medium">{{ $device->last_polled_at?->diffForHumans() ?: 'Never' }}</dd></div>
                <div><dt class="text-slate-500">Last Seen</dt><dd class="font-medium">{{ $device->last_seen_at?->diffForHumans() ?: 'Never' }}</dd></div>
            </dl>
        </section>
    </div>
</x-monitor-layout>
