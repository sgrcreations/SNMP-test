<x-monitor-layout
    title="Dashboard"
    header="Core Network Pulse"
    subheader="Live inventory, uplinks, and attention from polled SNMP data"
    :status="$polling_enabled ? 'Polling on' : 'Polling off'"
    :breadcrumbs="[['label' => 'Dashboard']]"
>
    {{-- KPI strip --}}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <div class="sgr-card p-4">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Device Health</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $stats['online'] }} / {{ $stats['total_devices'] }}</div>
            <p class="mt-1 text-xs text-cyan-700">{{ $stats['health_pct'] }}% online</p>
        </div>
        <div class="sgr-card p-4">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Devices Down</div>
            <div class="mt-2 text-2xl font-bold {{ $stats['offline'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $stats['offline'] }}</div>
            <p class="mt-1 text-xs text-slate-400">{{ $stats['unknown'] }} unknown</p>
        </div>
        <div class="sgr-card p-4">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Ports Down</div>
            <div class="mt-2 text-2xl font-bold {{ $stats['ports_down'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $stats['ports_down'] }}</div>
            <p class="mt-1 text-xs text-slate-400">{{ $stats['ports_down_devices'] }} devices affected</p>
        </div>
        <div class="sgr-card p-4">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Mapped Throughput</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $stats['throughput_label'] }}</div>
            <p class="mt-1 text-xs text-slate-400">{{ $stats['uplink_count'] }} marked uplink{{ $stats['uplink_count'] === 1 ? '' : 's' }}</p>
        </div>
        <div class="sgr-card p-4">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Capacity Used</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $stats['capacity_used_pct'] !== null ? $stats['capacity_used_pct'].'%' : '—' }}</div>
            <p class="mt-1 text-xs text-slate-400">of {{ $stats['capacity_label'] }}</p>
        </div>
        <div class="sgr-card p-4">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Peak Use</div>
            <div class="mt-2 text-2xl font-bold text-violet-700">{{ $stats['peak_util'] !== null ? $stats['peak_util'].'%' : '—' }}</div>
            <p class="mt-1 text-xs text-slate-400">Highest marked uplink util</p>
        </div>
    </div>

    {{-- Upstream traffic & capacity --}}
    <div class="mt-4 sgr-card p-5">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Upstream Traffic &amp; Capacity</h2>
                <p class="text-sm text-slate-500">
                    From ports marked as uplink on device Switch Ports.
                    @if($stats['uplink_count'] === 0)
                        Mark uplinks on a device to populate this panel.
                    @endif
                </p>
            </div>
            @can('devices.view')
                <a href="{{ route('devices.index') }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">View devices</a>
            @endcan
        </div>

        <div class="grid gap-6 xl:grid-cols-12">
            <div class="xl:col-span-4">
                <div class="text-3xl font-bold tracking-tight text-slate-900">{{ $stats['throughput_label'] }}</div>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-blue-50 px-3 py-2">
                        <dt class="text-xs text-blue-600">Download</dt>
                        <dd class="font-bold text-blue-800">{{ $stats['download_label'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-emerald-50 px-3 py-2">
                        <dt class="text-xs text-emerald-600">Upload</dt>
                        <dd class="font-bold text-emerald-800">{{ $stats['upload_label'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <dt class="text-xs text-slate-500">Capacity</dt>
                        <dd class="font-bold text-slate-800">{{ $stats['capacity_label'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-violet-50 px-3 py-2">
                        <dt class="text-xs text-violet-600">Peak Use</dt>
                        <dd class="font-bold text-violet-800">{{ $stats['peak_util'] !== null ? $stats['peak_util'].'%' : '—' }}</dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <div class="mb-1 flex justify-between text-xs text-slate-500">
                        <span>Throughput vs capacity</span>
                        <span>{{ $stats['capacity_used_pct'] !== null ? $stats['capacity_used_pct'].'%' : '—' }}</span>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-emerald-500" style="width: {{ min(100, (float) ($stats['capacity_used_pct'] ?? 0)) }}%"></div>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-5">
                <div class="mb-2 text-xs font-bold uppercase tracking-[0.12em] text-slate-400">Fleet sensors (live last_*)</div>
                <div class="grid gap-3 sm:grid-cols-3">
                    @php
                        $bars = [
                            ['CPU', $stats['avg_cpu'], '%', 'bg-cyan-500'],
                            ['Memory', $stats['avg_memory'], '%', 'bg-violet-500'],
                            ['Temp', $stats['avg_temperature'], '°C', 'bg-amber-400'],
                        ];
                    @endphp
                    @foreach($bars as [$label, $value, $suffix, $barClass])
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-3">
                            <div class="text-xs text-slate-500">{{ $label }}</div>
                            <div class="mt-1 text-xl font-bold">{{ $value !== null ? $value.$suffix : '—' }}</div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white">
                                <div class="h-full rounded-full {{ $barClass }}" style="width: {{ $value !== null ? min(100, (float) $value) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach                </div>
                @if($stats['onu_total'] > 0)
                    <div class="mt-4 rounded-2xl border border-slate-100 bg-white p-3">
                        <div class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">ONU fleet</div>
                        <div class="mt-2 flex flex-wrap gap-3 text-sm">
                            <span class="font-semibold text-emerald-700">{{ $stats['onu_online'] }} online</span>
                            <span class="font-semibold text-rose-600">{{ $stats['onu_offline'] }} offline</span>
                            <span class="text-slate-500">{{ $stats['onu_total'] }} total</span>
                            @if($stats['onu_critical_optical'] > 0)
                                <span class="font-semibold text-amber-700">{{ $stats['onu_critical_optical'] }} weak RX (&lt; −28 dBm)</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="xl:col-span-3">
                <div class="mb-2 text-xs font-bold uppercase tracking-[0.12em] text-slate-400">Uplink split</div>
                <div class="space-y-3">
                    @forelse($provider_split as $row)
                        <div>
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-slate-800">{{ $row['device'] }}</div>
                                    <div class="truncate text-xs text-slate-400">{{ $row['port'] }}</div>
                                </div>
                                <div class="shrink-0 text-right text-xs font-bold text-slate-700">{{ $row['bps_label'] }}</div>
                            </div>
                            <div class="mt-1 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-cyan-500" style="width: {{ min(100, (float) ($row['utilization'] ?? $row['share'])) }}%"></div>
                                </div>
                                <span class="w-12 text-right text-[11px] font-semibold text-slate-500">{{ $row['share'] }}%</span>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl bg-slate-50 px-3 py-4 text-sm text-slate-400">No marked uplinks yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-3">
        {{-- Attention --}}
        <div class="sgr-card p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Operational Attention</h2>
                <span class="text-xs text-slate-400">Live</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-center text-sm">
                <div class="rounded-xl bg-rose-50 p-3">
                    <div class="text-2xl font-bold text-rose-700">{{ $stats['ports_down'] }}</div>
                    <div class="text-xs text-rose-600">Ports down</div>
                </div>
                <div class="rounded-xl bg-amber-50 p-3">
                    <div class="text-2xl font-bold text-amber-700">{{ $stats['ports_down_devices'] }}</div>
                    <div class="text-xs text-amber-600">Devices affected</div>
                </div>
                <div class="rounded-xl bg-rose-50/70 p-3">
                    <div class="text-2xl font-bold text-rose-700">{{ $stats['offline'] }}</div>
                    <div class="text-xs text-rose-600">Devices down</div>
                </div>
                <div class="rounded-xl bg-amber-50/70 p-3">
                    <div class="text-2xl font-bold text-amber-700">{{ $stats['open_alerts'] }}</div>
                    <div class="text-xs text-amber-600">Open alerts</div>
                </div>
            </div>
            <ul class="mt-4 space-y-2">
                @forelse($open_alerts_list as $alert)
                    <li class="rounded-xl border border-slate-100 px-3 py-2 text-sm">
                        <div class="font-semibold text-slate-800">{{ $alert->title }}</div>
                        <div class="text-xs text-slate-400">{{ $alert->device?->name }} · {{ $alert->severity }}</div>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">No open alerts.</li>
                @endforelse
            </ul>
        </div>

        {{-- Availability by type --}}
        <div class="sgr-card p-5">
            <h2 class="font-semibold text-slate-900">Device Availability</h2>
            <div class="mt-4 space-y-4">
                @forelse($type_breakdown as $row)
                    @php $pct = $row['total'] > 0 ? round(($row['online'] / $row['total']) * 100) : 0; @endphp
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-semibold text-slate-700">{{ $row['label'] }}</span>
                            <span class="font-bold text-emerald-700">{{ $row['online'] }} / {{ $row['total'] }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Add devices to see type availability.</p>
                @endforelse
            </div>
            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-2">
                    <dt class="text-slate-500">Polling</dt>
                    <dd class="font-semibold {{ $polling_enabled ? 'text-emerald-600' : 'text-slate-700' }}">{{ $polling_enabled ? 'Enabled' : 'Disabled' }}</dd>
                </div>
                <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-2">
                    <dt class="text-slate-500">Last poll</dt>
                    <dd class="font-semibold">{{ $stats['last_poll'] ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Hot ports --}}
        <div class="sgr-card p-5">
            <h2 class="font-semibold text-slate-900">Hottest Ports</h2>
            <p class="text-xs text-slate-400">By utilization from last Sync / poll</p>
            <ul class="mt-4 space-y-2">
                @forelse($hot_ports as $port)
                    <li class="flex items-center justify-between gap-2 rounded-xl border border-slate-100 px-3 py-2 text-sm">
                        <div class="min-w-0">
                            <div class="truncate font-semibold">{{ $port->name }}</div>
                            <div class="truncate text-xs text-slate-400">{{ $port->device?->name }}</div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="font-bold {{ ($port->utilization ?? 0) >= 75 ? 'text-rose-600' : 'text-slate-800' }}">{{ $port->utilization !== null ? $port->utilization.'%' : '—' }}</div>
                            <div class="text-[10px] uppercase text-slate-400">{{ $port->oper_status }}</div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">No utilization samples yet — Sync devices.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <div class="sgr-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-slate-900">Highest CPU Devices</h2>
                    <p class="text-xs text-slate-400">From last_* on each device</p>
                </div>
                @can('devices.view')
                    <a href="{{ route('devices.index') }}" class="text-sm font-semibold text-cyan-600">View all</a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="sgr-table min-w-full text-left">
                    <thead class="bg-slate-50/80">
                    <tr>
                        <th>Device</th>
                        <th>CPU</th>
                        <th>Mem</th>
                        <th>Temp</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($hot_devices as $device)
                        <tr class="hover:bg-slate-50/70">
                            <td>
                                <div class="font-semibold">{{ $device->name }}</div>
                                <div class="text-xs text-slate-400">{{ $device->ip_address }}</div>
                            </td>
                            <td class="font-bold">{{ $device->last_cpu !== null ? $device->last_cpu.'%' : '—' }}</td>
                            <td>{{ $device->last_memory !== null ? $device->last_memory.'%' : '—' }}</td>
                            <td>{{ $device->last_temperature !== null ? $device->last_temperature.'°C' : '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('devices.show', $device) }}" class="text-sm font-semibold text-cyan-600">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No CPU samples yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sgr-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-slate-900">Inventory</h2>
                    <p class="text-xs text-slate-400">Recent devices</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="sgr-table min-w-full text-left">
                    <thead class="bg-slate-50/80">
                    <tr>
                        <th>Device</th>
                        <th>Reachability</th>
                        <th>Last poll</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($recent_devices as $device)
                        <tr class="hover:bg-slate-50/70">
                            <td>
                                <div class="font-semibold">{{ $device->name }}</div>
                                <div class="text-xs text-slate-400">{{ $device->ip_address }} · {{ $device->vendor?->label() }}</div>
                            </td>
                            <td>
                                @php $reach = $device->reachability?->value; @endphp
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold uppercase',
                                    'bg-emerald-50 text-emerald-700' => $reach === 'online',
                                    'bg-rose-50 text-rose-700' => $reach === 'offline',
                                    'bg-slate-100 text-slate-600' => ! in_array($reach, ['online', 'offline'], true),
                                ])>{{ $device->reachability?->label() }}</span>
                            </td>
                            <td class="text-sm text-slate-500">{{ $device->last_polled_at?->diffForHumans() ?: 'Never' }}</td>
                            <td class="text-end">
                                <a href="{{ route('devices.show', $device) }}" class="text-sm font-semibold text-cyan-600">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">No devices yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-monitor-layout>
