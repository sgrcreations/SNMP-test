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
        <button
            type="button"
            class="inline-flex h-10 items-center gap-2 rounded-xl border border-cyan-200 bg-cyan-50 px-4 text-sm font-semibold text-cyan-700 shadow-sm transition hover:bg-cyan-100"
            x-data
            @click="$dispatch('open-snmp-test')"
        >
            Test SNMP
        </button>
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

    <div
        class="mb-4"
        x-data="{
            open: false,
            loading: false,
            result: null,
            async runTest() {
                this.open = true;
                this.loading = true;
                this.result = null;
                try {
                    const response = await fetch('{{ route('devices.test-snmp', $device) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    });
                    this.result = await response.json();
                } catch (e) {
                    this.result = { connected: false, message: 'Request failed' };
                } finally {
                    this.loading = false;
                }
            }
        }"
        @open-snmp-test.window="runTest()"
    >
        <div class="mb-4 flex flex-wrap gap-2">
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $device->vendor?->label() }}</span>
            <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700">{{ $device->snmp_version?->label() }}</span>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $device->status?->label() }}</span>
            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $device->location ?: 'No location' }}</span>
        </div>

        <div x-show="open" x-cloak class="mb-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-400">SNMP Test Result</h2>
                <button type="button" class="text-xs font-semibold text-slate-400 hover:text-slate-700" @click="open = false">Close</button>
            </div>

            <div x-show="loading" class="mt-4 text-sm text-slate-500">Testing connection to {{ $device->displayEndpoint() }}...</div>

            <template x-if="!loading && result">
                <div class="mt-4 space-y-4">
                    <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide"
                         :class="result.connected ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">
                        <span class="h-1.5 w-1.5 rounded-full" :class="result.connected ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                        <span x-text="result.connected ? 'Connected' : 'Connection Failed'"></span>
                    </div>
                    <p class="text-sm text-slate-600" x-text="result.message"></p>

                    <template x-if="result.connected && result.system">
                        <dl class="grid gap-3 text-sm sm:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Hostname</dt><dd class="mt-1 font-semibold" x-text="result.system.hostname || '—'"></dd></div>
                            <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Uptime</dt><dd class="mt-1 font-semibold" x-text="result.system.uptime || '—'"></dd></div>
                            <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Location</dt><dd class="mt-1 font-semibold" x-text="result.system.location || '—'"></dd></div>
                            <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Contact</dt><dd class="mt-1 font-semibold" x-text="result.system.contact || '—'"></dd></div>
                            <div class="rounded-2xl bg-slate-50 px-3 py-3 sm:col-span-2"><dt class="text-xs text-slate-400">System Description</dt><dd class="mt-1 font-semibold break-all" x-text="result.system.description || '—'"></dd></div>
                            <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Interfaces Count</dt><dd class="mt-1 font-semibold" x-text="result.interfaces_count"></dd></div>
                        </dl>
                    </template>
                </div>
            </template>
        </div>
    </div>

    @if($latestMetric)
        <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="sgr-card p-4"><div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">CPU</div><div class="mt-2 text-2xl font-bold">{{ $latestMetric->cpu !== null ? $latestMetric->cpu.'%' : '—' }}</div></div>
            <div class="sgr-card p-4"><div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Memory</div><div class="mt-2 text-2xl font-bold">{{ $latestMetric->memory !== null ? $latestMetric->memory.'%' : '—' }}</div></div>
            <div class="sgr-card p-4"><div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">RX Bytes</div><div class="mt-2 text-2xl font-bold">{{ $latestMetric->rx_bytes ? number_format((float) $latestMetric->rx_bytes) : '—' }}</div></div>
            <div class="sgr-card p-4"><div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">TX Bytes</div><div class="mt-2 text-2xl font-bold">{{ $latestMetric->tx_bytes ? number_format((float) $latestMetric->tx_bytes) : '—' }}</div></div>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="sgr-card p-5">
            <h2 class="mb-4 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Device Details</h2>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Hostname</dt><dd class="mt-1 font-semibold text-slate-800">{{ $device->hostname ?: '—' }}</dd></div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Model</dt><dd class="mt-1 font-semibold text-slate-800">{{ $device->model ?: '—' }}</dd></div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Location</dt><dd class="mt-1 font-semibold text-slate-800">{{ $device->location ?: '—' }}</dd></div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Polling Interval</dt><dd class="mt-1 font-semibold text-slate-800">{{ $device->polling_interval }}s</dd></div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3 sm:col-span-2"><dt class="text-xs text-slate-400">Description</dt><dd class="mt-1 font-semibold text-slate-800">{{ $device->description ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="sgr-card p-5">
            <h2 class="mb-4 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">SNMP Profile</h2>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Version</dt><dd class="mt-1 font-semibold text-slate-800">{{ $device->snmp_version?->label() }}</dd></div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Port</dt><dd class="mt-1 font-semibold text-slate-800">{{ $device->port }}</dd></div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Last Polled</dt><dd class="mt-1 font-semibold text-slate-800">{{ $device->last_polled_at?->diffForHumans() ?: 'Never' }}</dd></div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Last Seen</dt><dd class="mt-1 font-semibold text-slate-800">{{ $device->last_seen_at?->diffForHumans() ?: 'Never' }}</dd></div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Credentials</dt><dd class="mt-1 font-semibold text-emerald-600">Encrypted at rest</dd></div>
                <div class="rounded-2xl bg-slate-50 px-3 py-3"><dt class="text-xs text-slate-400">Uptime (last poll)</dt><dd class="mt-1 font-semibold text-slate-800">{{ $latestMetric?->uptime ?: '—' }}</dd></div>
            </dl>
        </section>
    </div>

    <section class="sgr-card mt-4 overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-semibold text-slate-900">Interfaces</h2>
            <a href="{{ route('interfaces.index', ['device_id' => $device->id]) }}" class="text-sm font-semibold text-cyan-600 hover:text-cyan-700">View all</a>
        </div>
        <div class="overflow-x-auto">
            <table class="sgr-table min-w-full text-left">
                <thead class="bg-slate-50/80">
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Speed</th>
                    <th>RX</th>
                    <th>TX</th>
                    <th>Errors</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($device->interfaces as $interface)
                    <tr>
                        <td>
                            <div class="font-semibold">{{ $interface->name }}</div>
                            <div class="text-xs text-slate-400">{{ $interface->description }}</div>
                        </td>
                        <td class="uppercase">{{ $interface->oper_status }}</td>
                        <td>{{ $interface->speed ? number_format($interface->speed) : '—' }}</td>
                        <td>{{ number_format((float) $interface->rx_bytes) }}</td>
                        <td>{{ number_format((float) $interface->tx_bytes) }}</td>
                        <td>{{ $interface->errors }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-400">No interfaces yet. Use Test SNMP or wait for the next poll cycle.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-monitor-layout>
