@php
    $reach = $device->reachability?->value;
    $statusLabel = match ($reach) {
        'online' => 'Online',
        'offline' => 'Offline',
        default => 'Unknown',
    };
@endphp

<x-monitor-layout
    title="{{ $device->name }}"
    header="{{ $device->name }}"
    :status="$statusLabel"
    :meta="($device->model ?: 'No model').' · '.$device->displayEndpoint().' · Poll every '.$device->polling_interval.'s'"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Devices', 'url' => route('devices.index')],
        ['label' => $device->name],
    ]"
>
    <x-slot:actions>
        <button type="button" onclick="window.location.reload()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-800" title="Refresh">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
        </button>
        @can('devices.update')
            <a href="{{ route('devices.edit', $device) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-800" title="Edit">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.1 2.1 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </a>
        @endcan
        @can('devices.delete')
            <form method="POST" action="{{ route('devices.destroy', $device) }}" onsubmit="return confirm('Delete this device?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-rose-200 bg-white text-rose-500 shadow-sm transition hover:bg-rose-50" title="Delete">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 12a1 1 0 001 1h6a1 1 0 001-1l1-12"/></svg>
                </button>
            </form>
        @endcan
    </x-slot:actions>

    <div class="mb-4 flex flex-wrap gap-2">
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $device->vendor?->label() }}</span>
        <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700">{{ $device->snmp_version?->label() }}</span>
        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $device->status?->label() }}</span>
        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $device->location ?: 'No location' }}</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="sgr-card p-5">
            <h2 class="mb-4 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Device Details</h2>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-xs text-slate-400">Hostname</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $device->hostname ?: '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-xs text-slate-400">Model</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $device->model ?: '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-xs text-slate-400">Location</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $device->location ?: '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-xs text-slate-400">Polling Interval</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $device->polling_interval }}s</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3 sm:col-span-2">
                    <dt class="text-xs text-slate-400">Description</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $device->description ?: '—' }}</dd>
                </div>
            </dl>
        </section>

        <section class="sgr-card p-5">
            <h2 class="mb-4 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">SNMP Profile</h2>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-xs text-slate-400">Version</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $device->snmp_version?->label() }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-xs text-slate-400">Port</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $device->port }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-xs text-slate-400">Username</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $device->username ?: '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-xs text-slate-400">Auth Protocol</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $device->auth_protocol?->label() ?: '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-xs text-slate-400">Privacy Protocol</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $device->priv_protocol?->label() ?: '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-xs text-slate-400">Credentials</dt>
                    <dd class="mt-1 font-semibold text-emerald-600">Encrypted at rest</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-xs text-slate-400">Last Polled</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $device->last_polled_at?->diffForHumans() ?: 'Never' }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3">
                    <dt class="text-xs text-slate-400">Last Seen</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $device->last_seen_at?->diffForHumans() ?: 'Never' }}</dd>
                </div>
            </dl>
        </section>
    </div>
</x-monitor-layout>
