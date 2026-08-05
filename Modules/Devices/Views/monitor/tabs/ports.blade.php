@php
    $fmtBps = function ($bps): string {
        if ($bps === null || $bps === '') {
            return '0 bps';
        }
        $v = (float) $bps;
        if ($v >= 1_000_000) {
            return number_format($v / 1_000_000, 2).' Mbps';
        }
        if ($v >= 1_000) {
            return number_format($v / 1_000, 2).' Kbps';
        }

        return number_format($v, 2).' bps';
    };
    $fmtBytes = function ($bytes): string {
        $v = (float) $bytes;
        if ($v >= 1_000_000_000_000) {
            return number_format($v / 1_000_000_000_000, 2).' TB';
        }
        if ($v >= 1_000_000_000) {
            return number_format($v / 1_000_000_000, 2).' GB';
        }
        if ($v >= 1_000_000) {
            return number_format($v / 1_000_000, 2).' MB';
        }
        if ($v >= 1_000) {
            return number_format($v / 1_000, 2).' KB';
        }

        return number_format($v, 0).' B';
    };
    $rows = $device->isOlt() ? $accessPorts : $device->interfaces;
    $uplinkNames = $rows->where('is_uplink', true)->pluck('name')->values();
@endphp

<div
    class="sgr-card overflow-hidden"
    x-data="portPanel(@js(url('/devices/'.$device->id.'/interfaces')))"
>
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="font-semibold">{{ $device->isOlt() ? 'Uplink / Access Ports' : 'Fabric Interfaces' }}</h3>
            <p class="text-xs text-slate-400">
                {{ $rows->count() }} ports from last SNMP poll
                @if($uplinkNames->isNotEmpty())
                    · Marked uplinks: <span class="font-semibold text-cyan-700">{{ $uplinkNames->implode(', ') }}</span>
                    — chart on
                    <a href="{{ route('devices.show', ['device' => $device, 'tab' => 'overview']) }}" class="font-semibold text-cyan-700 hover:underline">Overview → Device Uplink Traffic</a>
                @endif
            </p>
        </div>
        <form method="GET" class="w-full max-w-xs sm:w-auto">
            <input type="hidden" name="tab" value="ports">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search interfaces..." class="sgr-input">
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="sgr-table min-w-full text-left">
            <thead class="bg-slate-50/80">
            <tr>
                <th>Port</th>
                <th>Status</th>
                <th>Speed</th>
                <th>Traffic IN</th>
                <th>Traffic OUT</th>
                <th>Util</th>
                <th>RX dBm</th>
                <th>TX dBm</th>
                <th>Errors</th>
                <th>Uplink</th>
                <th class="w-16">View</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($rows->when(request('q'), fn($c) => $c->filter(fn($i) => str_contains(strtolower($i->name.' '.$i->description), strtolower(request('q'))))) as $iface)
                @php
                    $portPayload = [
                        'id' => $iface->id,
                        'name' => $iface->name,
                        'description' => $iface->description ?: ('ifIndex '.$iface->if_index),
                        'if_index' => $iface->if_index,
                        'oper_status' => $iface->oper_status,
                        'speed' => $iface->speedLabel(),
                        'rx_bps' => $fmtBps($iface->rx_bps),
                        'tx_bps' => $fmtBps($iface->tx_bps),
                        'rx_bytes' => $fmtBytes($iface->rx_bytes),
                        'tx_bytes' => $fmtBytes($iface->tx_bytes),
                        'utilization' => $iface->utilization !== null ? $iface->utilization.'%' : '—',
                        'rx_power' => $iface->rx_power_dbm !== null ? number_format($iface->rx_power_dbm, 1).' dBm' : '—',
                        'tx_power' => $iface->tx_power_dbm !== null ? number_format($iface->tx_power_dbm, 1).' dBm' : '—',
                        'temperature' => $iface->temperature !== null ? $iface->temperature.'°C' : '—',
                        'errors' => (string) $iface->errors,
                        'is_uplink' => (bool) $iface->is_uplink,
                        'port_role' => $iface->port_role ?: '—',
                        'last_polled' => $iface->last_polled_at ? app_time($iface->last_polled_at) : '—',
                    ];
                @endphp
                <tr class="hover:bg-slate-50/70">
                    <td>
                        <div class="font-semibold">{{ $iface->name }}</div>
                        <div class="text-xs text-slate-400">{{ $iface->description ?: 'ifIndex '.$iface->if_index }}</div>
                    </td>
                    <td>
                        <span @class([
                            'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold uppercase',
                            'bg-emerald-50 text-emerald-700' => $iface->oper_status === 'up',
                            'bg-rose-50 text-rose-700' => $iface->oper_status === 'down',
                            'bg-slate-100 text-slate-600' => !in_array($iface->oper_status, ['up','down'], true),
                        ])>{{ $iface->oper_status }}</span>
                    </td>
                    <td>
                        <span class="rounded-full bg-sky-50 px-2 py-1 text-[11px] font-bold text-sky-700">{{ $iface->speedLabel() }}</span>
                    </td>
                    <td class="text-blue-600 font-medium">↓ {{ $fmtBps($iface->rx_bps) }}</td>
                    <td class="text-emerald-600 font-medium">↑ {{ $fmtBps($iface->tx_bps) }}</td>
                    <td>{{ $iface->utilization !== null ? $iface->utilization.'%' : '—' }}</td>
                    <td class="font-mono text-xs {{ $iface->rx_power_dbm !== null && $iface->rx_power_dbm < -28 ? 'font-bold text-rose-600' : 'text-slate-600' }}">
                        {{ $iface->rx_power_dbm !== null ? number_format($iface->rx_power_dbm, 1) : '—' }}
                    </td>
                    <td class="font-mono text-xs text-slate-600">
                        {{ $iface->tx_power_dbm !== null ? number_format($iface->tx_power_dbm, 1) : '—' }}
                    </td>
                    <td>{{ $iface->errors }}</td>
                    <td>
                        @can('devices.update')
                            <form method="POST" action="{{ route('devices.interfaces.toggle-uplink', [$device, $iface->id]) }}">
                                @csrf
                                <button class="rounded-lg border px-2 py-1 text-[11px] font-bold {{ $iface->is_uplink ? 'border-cyan-200 bg-cyan-50 text-cyan-700' : 'border-slate-200 text-slate-500' }}">
                                    {{ $iface->is_uplink ? 'UPLINK' : 'Mark' }}
                                </button>
                            </form>
                        @else
                            {{ $iface->is_uplink ? 'Yes' : '—' }}
                        @endcan
                    </td>
                    <td>
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-700"
                            title="View port details"
                            @click="show(@js($portPayload))"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="px-5 py-10 text-center text-slate-400">No interface rows yet. Sync this device to pull IF-MIB data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Per-port detail popup with traffic graph --}}
    <div
        x-cloak
        x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-slate-900/40" @click="close()"></div>
        <div
            class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-xl"
            @keydown.escape.window="close()"
        >
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Interface</div>
                    <h3 class="mt-1 text-lg font-bold text-slate-900" x-text="port?.name"></h3>
                    <p class="text-sm text-slate-500" x-text="port?.description"></p>
                </div>
                <button type="button" class="rounded-lg border border-slate-200 px-2 py-1 text-sm text-slate-500 hover:bg-slate-50" @click="close()">Close</button>
            </div>

            <div class="mb-4 rounded-xl border border-slate-100 bg-slate-50/80 p-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Traffic history</div>
                    <div class="flex gap-1">
                        <template x-for="r in ['6h','24h','7d']" :key="r">
                            <button
                                type="button"
                                class="rounded-lg px-2 py-1 text-[11px] font-bold"
                                :class="range === r ? 'bg-cyan-50 text-cyan-700' : 'text-slate-400 hover:bg-white'"
                                @click="setRange(r)"
                                x-text="r.toUpperCase()"
                            ></button>
                        </template>
                    </div>
                </div>
                <div id="portTrafficChart" class="h-56 w-full"></div>
                <p class="mt-2 text-xs text-slate-400" x-show="chartEmpty" x-cloak>
                    No samples yet for this range. Sync the device twice (about a minute apart) to build the traffic graph.
                </p>
                <p class="mt-2 text-xs text-slate-400" x-show="chartLoading" x-cloak>Loading chart…</p>
            </div>

            <template x-if="port">
                <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-400">Status</dt>
                        <dd class="mt-1 font-semibold uppercase" x-text="port.oper_status"></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-400">Speed</dt>
                        <dd class="mt-1 font-semibold" x-text="port.speed"></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-400">Utilization</dt>
                        <dd class="mt-1 font-semibold" x-text="port.utilization"></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-400">Traffic IN</dt>
                        <dd class="mt-1 font-semibold text-blue-600" x-text="'↓ ' + port.rx_bps"></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-400">Traffic OUT</dt>
                        <dd class="mt-1 font-semibold text-emerald-600" x-text="'↑ ' + port.tx_bps"></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-400">Errors</dt>
                        <dd class="mt-1 font-semibold" x-text="port.errors"></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-400">RX total</dt>
                        <dd class="mt-1 font-semibold" x-text="port.rx_bytes"></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-400">TX total</dt>
                        <dd class="mt-1 font-semibold" x-text="port.tx_bytes"></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-400">RX Power</dt>
                        <dd class="mt-1 font-semibold" x-text="port.rx_power"></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-400">TX Power</dt>
                        <dd class="mt-1 font-semibold" x-text="port.tx_power"></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-400">Port Temp</dt>
                        <dd class="mt-1 font-semibold" x-text="port.temperature"></dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-400">Role / Uplink</dt>
                        <dd class="mt-1 font-semibold" x-text="port.port_role + (port.is_uplink ? ' · uplink' : '')"></dd>
                    </div>
                    <div class="col-span-2 rounded-xl bg-slate-50 p-3 sm:col-span-3">
                        <dt class="text-xs text-slate-400">ifIndex · Last polled</dt>
                        <dd class="mt-1 font-semibold" x-text="port.if_index + ' · ' + port.last_polled"></dd>
                    </div>
                </dl>
            </template>

            <p class="mt-4 text-xs text-slate-500" x-show="port?.is_uplink">
                This port is marked as uplink. Combined traffic appears on
                <a href="{{ route('devices.show', ['device' => $device, 'tab' => 'overview']) }}" class="font-semibold text-cyan-700 hover:underline">Overview → Device Uplink Traffic</a>.
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function portPanel(metricsBase) {
    return {
        open: false,
        port: null,
        range: '24h',
        chartLoading: false,
        chartEmpty: false,
        chart: null,
        metricsBase: String(metricsBase || '').replace(/\/$/, ''),

        show(p) {
            this.port = p;
            this.open = true;
            this.range = '24h';
            this.$nextTick(() => this.loadChart());
        },

        close() {
            this.open = false;
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        },

        setRange(r) {
            this.range = r;
            this.loadChart();
        },

        async loadChart() {
            if (!this.port?.id || !window.ApexCharts) return;
            this.chartLoading = true;
            this.chartEmpty = false;
            try {
                const url = `${this.metricsBase}/${this.port.id}/metrics.json?range=${encodeURIComponent(this.range)}`;
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('metrics failed');
                const data = await res.json();
                const cats = data.categories || [];
                this.chartEmpty = cats.length === 0;

                const options = {
                    chart: { type: 'area', height: 220, toolbar: { show: false }, animations: { enabled: true } },
                    stroke: { curve: 'smooth', width: 2 },
                    colors: ['#2563eb', '#059669'],
                    series: [
                        { name: 'RX Mbps', data: data.rx_mbps || [] },
                        { name: 'TX Mbps', data: data.tx_mbps || [] },
                    ],
                    xaxis: { categories: cats, labels: { style: { colors: '#94a3b8', fontSize: '10px' } } },
                    yaxis: { labels: { style: { colors: '#94a3b8' }, formatter: (v) => Number(v).toFixed(2) } },
                    dataLabels: { enabled: false },
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
                    grid: { borderColor: '#e2e8f0' },
                    legend: { position: 'top' },
                    noData: { text: 'No samples yet' },
                };

                if (this.chart) {
                    this.chart.updateOptions({ xaxis: { categories: cats } });
                    this.chart.updateSeries(options.series);
                } else {
                    const el = document.querySelector('#portTrafficChart');
                    if (!el) return;
                    this.chart = new ApexCharts(el, options);
                    await this.chart.render();
                }
            } catch (e) {
                this.chartEmpty = true;
            } finally {
                this.chartLoading = false;
            }
        },
    };
}
</script>
@endpush
