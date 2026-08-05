@php
    $fabric = $fabric ?? ['status' => ['up' => 0, 'down' => 0, 'other' => 0], 'top_util' => [], 'top_errors' => []];
    $cards = [
        ['Interfaces', $overview['interfaces_total'], ($overview['interfaces_up'] ?? 0).' online', 'emerald', 'card-if'],
        ['VLANs', $overview['vlans_total'], ($overview['vlans_active'] ?? 0).' active', 'sky', 'card-vlan'],
        ['Uplinks', ($overview['uplinks_up'] ?? 0).'/'.($overview['uplinks_total'] ?? 0), 'Marked ports', 'cyan', 'card-upl'],
        ['Errors', number_format((int) ($overview['errors_total'] ?? 0)), 'IF counters', 'amber', 'card-err'],
        ['Port usage', ($overview['port_usage'] ?? 0).'%', ($overview['interfaces_up'] ?? 0).' links up', 'violet', 'card-port'],
        ['Latency', ($overview['latency_ms'] ?? '—').($overview['latency_ms'] !== null ? ' ms' : ''), 'ICMP', 'rose', 'card-lat'],
    ];
    $profile = $pollingProfile ?? [
        'interval_label' => 'every '.$device->polling_interval.'s',
        'source_label' => 'Laravel',
        'next_due_label' => '—',
        'live_refresh_seconds' => 60,
        'source' => 'laravel',
    ];
@endphp

<div class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
    <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Polling</span>
    <span class="font-semibold text-slate-900">Polls {{ $profile['interval_label'] }}</span>
    <span class="text-slate-300">·</span>
    <span class="text-slate-600">Source: <strong class="font-semibold text-slate-800">{{ $profile['source_label'] }}</strong></span>
    <span class="text-slate-300">·</span>
    <span class="text-slate-600">{{ $profile['next_due_label'] }}</span>
    @if(($profile['source'] ?? '') === 'snmp-agent')
        <span id="liveRefreshBadge" class="ml-auto inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">
            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
        Live · every {{ $profile['live_refresh_seconds'] }}s while viewing
        </span>
    @endif
</div>

<div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
    @foreach($cards as [$label, $value, $sub, $tone, $id])
        <div class="sgr-card p-4" id="{{ $id }}">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ $label }}</div>
            <div class="mt-2 text-2xl font-bold text-slate-900 card-value">{{ $value }}</div>
            <div class="mt-1 text-xs text-slate-400 card-sub">{{ $sub }}</div>
        </div>
    @endforeach
</div>

<div class="mb-4 grid gap-4 xl:grid-cols-3">
    <div class="sgr-card p-4 xl:col-span-2">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="font-semibold">Port health</h3>
                <p class="text-xs text-slate-400">Physical fabric status, hottest util, and error leaders</p>
            </div>
            <a href="{{ route('devices.show', ['device' => $device, 'tab' => 'ports']) }}" class="text-xs font-semibold text-cyan-700 hover:underline">View all ports →</a>
        </div>
        <div class="grid gap-4 lg:grid-cols-3">
            <div>
                <div class="mb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">Port status</div>
                <div id="portStatusChart" class="h-56"></div>
            </div>
            <div class="lg:col-span-2">
                <div class="mb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">Top utilization</div>
                <div id="topUtilChart" class="h-56"></div>
                @if(count($fabric['top_util'] ?? []) === 0)
                    <p class="mt-1 text-xs text-slate-400">No utilization samples yet. Sync twice so rates can be calculated.</p>
                @endif
            </div>
        </div>
        <div class="mt-4 border-t border-slate-100 pt-4">
            <div class="mb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">Error leaders</div>
            <div id="topErrorsChart" class="h-48"></div>
            @if(count($fabric['top_errors'] ?? []) === 0)
                <p class="mt-1 text-xs text-slate-400">No non-zero error counters on physical ports.</p>
            @endif
        </div>
    </div>

    <div class="sgr-card p-4">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h3 class="font-semibold">Hardware Identity</h3>
            <a href="{{ route('devices.edit', $device) }}" class="text-xs font-semibold text-cyan-700 hover:underline">View all →</a>
        </div>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-slate-400">Hostname</dt><dd class="font-semibold">{{ $device->hostname ?: '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-400">Manufacturer</dt><dd class="font-semibold">{{ $device->manufacturer ?: $device->vendor?->label() }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-400">Model</dt><dd class="font-semibold">{{ $device->model ?: '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-400">Serial</dt><dd class="font-semibold">{{ $device->serial_number ?: '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-400">Management IP</dt><dd class="font-semibold">{{ $device->ip_address }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-400">Area</dt><dd class="font-semibold">{{ $device->area ?: ($device->location ?: '—') }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-400">Firmware</dt><dd class="font-semibold">{{ $device->firmware ?: '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-400">Uptime</dt><dd class="font-semibold text-amber-600">{{ $device->sys_uptime ?: '—' }}</dd></div>
        </dl>
    </div>
</div>

<div class="mb-4 grid gap-4 xl:grid-cols-2">
    <div class="sgr-card p-4">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h3 class="font-semibold">Device Uplink Traffic</h3>
                @php
                    $markedUplinks = $device->interfaces->where('is_uplink', true);
                    $ulRx = $markedUplinks->sum(fn ($i) => (float) ($i->rx_bps ?? 0));
                    $ulTx = $markedUplinks->sum(fn ($i) => (float) ($i->tx_bps ?? 0));
                    $ulCap = $markedUplinks->sum(fn ($i) => (float) ($i->speed ?? 0));
                    $ulFmt = function (float $bps): string {
                        if ($bps >= 1_000_000_000) return number_format($bps / 1_000_000_000, 2).' Gbps';
                        if ($bps >= 1_000_000) return number_format($bps / 1_000_000, 2).' Mbps';
                        if ($bps >= 1_000) return number_format($bps / 1_000, 2).' Kbps';
                        return number_format($bps, 0).' bps';
                    };
                @endphp
                @if($markedUplinks->isNotEmpty())
                    <p class="mt-0.5 text-xs text-slate-400">{{ $markedUplinks->pluck('name')->implode(', ') }} ·
                        IN {{ $ulFmt($ulRx) }} · OUT {{ $ulFmt($ulTx) }} · Cap {{ $ulFmt($ulCap) }}
                    </p>
                @endif
            </div>
            <a href="{{ route('devices.show', ['device' => $device, 'tab' => 'ports']) }}" class="text-xs font-semibold text-cyan-700 hover:underline">Mark ports →</a>
        </div>
        @if($markedUplinks->isNotEmpty())
            <div class="mb-3 grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-xl bg-blue-50 p-2"><div class="text-blue-500">Download</div><div class="font-bold text-blue-800">{{ $ulFmt($ulRx) }}</div></div>
                <div class="rounded-xl bg-emerald-50 p-2"><div class="text-emerald-500">Upload</div><div class="font-bold text-emerald-800">{{ $ulFmt($ulTx) }}</div></div>
                <div class="rounded-xl bg-slate-50 p-2"><div class="text-slate-400">Capacity</div><div class="font-bold text-slate-800">{{ $ulFmt($ulCap) }}</div></div>
            </div>
        @endif
        <div id="trafficChart" class="h-64"></div>
        @if(empty($trafficSeries['mapped']))
            <p class="mt-2 text-sm text-slate-400">No uplink port is mapped. Open Switch Ports and mark a port as device uplink.</p>
        @elseif(count($trafficSeries['categories']) === 0)
            <p class="mt-2 text-sm text-slate-400">Live rates above are from the last poll. Sync again to build history.</p>
        @endif
    </div>
    <div class="sgr-card p-4">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold">Network Quality</h3>
            <div class="flex gap-1">
                @foreach(['6h','24h','7d'] as $r)
                    <a href="{{ route('devices.show', ['device' => $device, 'tab' => 'overview', 'range' => $r]) }}"
                       class="rounded-lg px-2 py-1 text-[11px] font-bold {{ $range === $r ? 'bg-cyan-50 text-cyan-700' : 'text-slate-400 hover:bg-slate-50' }}">{{ strtoupper($r) }}</a>
                @endforeach
            </div>
        </div>
        <div id="qualityChart" class="h-64"></div>
        @if(count($qualitySeries['categories']) === 0)
            <p class="mt-2 text-sm text-slate-400">No ping samples for this range. Local ICMP history is only written when not using snmp-agent.</p>
        @else
            <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-xl bg-slate-50 p-2"><div class="text-slate-400">Latency</div><div class="font-bold text-sky-600">{{ $overview['latency_ms'] !== null ? $overview['latency_ms'].' ms' : '—' }}</div></div>
                <div class="rounded-xl bg-slate-50 p-2"><div class="text-slate-400">Jitter</div><div class="font-bold text-amber-600">{{ $overview['jitter_ms'] !== null ? $overview['jitter_ms'].' ms' : '—' }}</div></div>
                <div class="rounded-xl bg-slate-50 p-2"><div class="text-slate-400">Loss</div><div class="font-bold text-rose-600">{{ $overview['packet_loss_pct'] !== null ? $overview['packet_loss_pct'].'%' : '—' }}</div></div>
            </div>
        @endif
    </div>
</div>

<div class="grid gap-4 xl:grid-cols-2">
    <div class="sgr-card p-4">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h3 class="font-semibold">Software & Capacity</h3>
            <a href="{{ route('devices.show', ['device' => $device, 'tab' => 'ports']) }}" class="text-xs font-semibold text-cyan-700 hover:underline">View all →</a>
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-400">Active Ports</dt><dd class="mt-1 text-xl font-bold">{{ $overview['interfaces_up'] }} / {{ $overview['interfaces_total'] }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-400">Open Alerts</dt><dd class="mt-1 text-xl font-bold">{{ $overview['open_alerts'] }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-400">Type</dt><dd class="mt-1 font-bold">{{ $device->device_type?->label() }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-400">Last Poll</dt><dd class="mt-1 font-bold">{{ $device->last_polled_at?->diffForHumans() ?: 'Never' }}</dd></div>
        </dl>
    </div>
    <div class="sgr-card p-4">
        <h3 class="font-semibold">Port Usage</h3>
        <div class="mt-4 flex items-end gap-4">
            <div class="text-4xl font-bold text-cyan-700" id="portUsageBig">{{ $overview['port_usage'] }}%</div>
            <div class="flex-1">
                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                    <div id="portUsageBar" class="h-full rounded-full bg-cyan-500" style="width: {{ min(100, $overview['port_usage']) }}%"></div>
                </div>
                <p class="mt-2 text-xs text-slate-400" id="portUsageHint">{{ $overview['interfaces_up'] }} links up of {{ $overview['interfaces_total'] }} interfaces</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const metricsUrl = @json(route('devices.metrics', $device));
    const range = @json($range);
    const refreshMs = {{ (int) ($profile['live_refresh_seconds'] ?? 60) }} * 1000;
    const liveEnabled = @json(($profile['source'] ?? '') === 'snmp-agent');

    let fabric = @json($fabric);
    const traffic = @json($trafficSeries);
    const quality = @json($qualitySeries);

    let portStatusChart = null;
    let topUtilChart = null;
    let topErrorsChart = null;
    let trafficChart = null;

    const fmtBps = (bps) => {
        const v = Number(bps || 0);
        if (v >= 1e9) return (v / 1e9).toFixed(2) + ' Gbps';
        if (v >= 1e6) return (v / 1e6).toFixed(2) + ' Mbps';
        if (v >= 1e3) return (v / 1e3).toFixed(2) + ' Kbps';
        return Math.round(v) + ' bps';
    };

    const shortName = (n) => {
        const s = String(n || '');
        return s.length > 18 ? s.slice(0, 16) + '…' : s;
    };

    function renderFabricCharts(data) {
        if (!window.ApexCharts) return;
        const status = data.status || { up: 0, down: 0, other: 0 };
        const utilRows = data.top_util || [];
        const errRows = data.top_errors || [];

        const statusSeries = [status.up || 0, status.down || 0, status.other || 0];
        if (!portStatusChart) {
            portStatusChart = new ApexCharts(document.querySelector('#portStatusChart'), {
                chart: { type: 'donut', height: 220, toolbar: { show: false } },
                labels: ['Up', 'Down', 'Other'],
                colors: ['#059669', '#e11d48', '#94a3b8'],
                series: statusSeries,
                legend: { position: 'bottom' },
                dataLabels: { enabled: false },
                plotOptions: { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: 'Ports' } } } } },
                noData: { text: 'No ports' },
            });
            portStatusChart.render();
        } else {
            portStatusChart.updateSeries(statusSeries);
        }

        const utilCats = utilRows.map((r) => shortName(r.name));
        const utilData = utilRows.map((r) => Number(r.utilization || 0));
        if (!topUtilChart) {
            topUtilChart = new ApexCharts(document.querySelector('#topUtilChart'), {
                chart: { type: 'bar', height: 220, toolbar: { show: false } },
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '70%' } },
                colors: ['#0891b2'],
                series: [{ name: 'Util %', data: utilData }],
                xaxis: { categories: utilCats, max: 100, labels: { style: { colors: '#94a3b8' } } },
                dataLabels: { enabled: true, formatter: (v) => `${v}%` },
                grid: { borderColor: '#e2e8f0' },
                noData: { text: 'Waiting for util…' },
            });
            topUtilChart.render();
        } else {
            topUtilChart.updateOptions({ xaxis: { categories: utilCats } });
            topUtilChart.updateSeries([{ name: 'Util %', data: utilData }]);
        }

        const errCats = errRows.map((r) => shortName(r.name));
        const errData = errRows.map((r) => Number(r.errors || 0));
        if (!topErrorsChart) {
            topErrorsChart = new ApexCharts(document.querySelector('#topErrorsChart'), {
                chart: { type: 'bar', height: 180, toolbar: { show: false } },
                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                colors: ['#f59e0b'],
                series: [{ name: 'Errors', data: errData }],
                xaxis: { categories: errCats, labels: { rotate: -35, style: { colors: '#94a3b8', fontSize: '10px' } } },
                dataLabels: { enabled: false },
                grid: { borderColor: '#e2e8f0' },
                noData: { text: 'No error data' },
            });
            topErrorsChart.render();
        } else {
            topErrorsChart.updateOptions({ xaxis: { categories: errCats } });
            topErrorsChart.updateSeries([{ name: 'Errors', data: errData }]);
        }
    }

    renderFabricCharts(fabric);

    if (window.ApexCharts) {
        if (traffic.categories?.length) {
            trafficChart = new ApexCharts(document.querySelector('#trafficChart'), {
                chart: { type: 'line', height: 250, toolbar: { show: false } },
                stroke: { curve: 'smooth', width: 2 },
                colors: ['#2563eb', '#059669'],
                series: [
                    { name: 'RX Mbps', data: traffic.rx_mbps },
                    { name: 'TX Mbps', data: traffic.tx_mbps },
                ],
                xaxis: { categories: traffic.categories },
                dataLabels: { enabled: false },
                grid: { borderColor: '#e2e8f0' },
            });
            trafficChart.render();
        }

        if (quality.categories?.length) {
            new ApexCharts(document.querySelector('#qualityChart'), {
                chart: { type: 'line', height: 250, toolbar: { show: false } },
                stroke: { curve: 'smooth', width: 2 },
                colors: ['#2563eb', '#f59e0b', '#e11d48'],
                series: [
                    { name: 'Latency ms', data: quality.latency },
                    { name: 'Jitter ms', data: quality.jitter },
                    { name: 'Loss %', data: quality.loss },
                ],
                xaxis: { categories: quality.categories },
                dataLabels: { enabled: false },
                grid: { borderColor: '#e2e8f0' },
                legend: { position: 'top' },
            }).render();
        }
    }

    async function refreshOverview() {
        if (document.hidden || !liveEnabled) return;
        const badge = document.getElementById('liveRefreshBadge');
        if (badge) badge.classList.add('opacity-70');
        try {
            const res = await fetch(`${metricsUrl}?range=${encodeURIComponent(range)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const payload = await res.json();
            if (payload.fabric) {
                fabric = payload.fabric;
                renderFabricCharts(fabric);
            }
            if (payload.traffic?.categories?.length && trafficChart) {
                trafficChart.updateOptions({ xaxis: { categories: payload.traffic.categories } });
                trafficChart.updateSeries([
                    { name: 'RX Mbps', data: payload.traffic.rx_mbps || [] },
                    { name: 'TX Mbps', data: payload.traffic.tx_mbps || [] },
                ]);
            }
            const live = payload.uplink_live;
            if (live) {
                const cards = document.querySelectorAll('#trafficChart')[0]?.closest('.sgr-card')?.querySelectorAll('.grid.grid-cols-3 .font-bold');
                if (cards && cards.length >= 3) {
                    cards[0].textContent = fmtBps(live.rx_bps);
                    cards[1].textContent = fmtBps(live.tx_bps);
                    cards[2].textContent = fmtBps(live.capacity_bps);
                }
            }
            const ov = payload.overview;
            if (!ov) return;
            const setCard = (id, value, sub) => {
                const el = document.querySelector(`#${id} .card-value`);
                const subEl = document.querySelector(`#${id} .card-sub`);
                if (el && value !== undefined && value !== null) el.textContent = value;
                if (subEl && sub) subEl.textContent = sub;
            };
            setCard('card-if', ov.interfaces_total, `${ov.interfaces_up ?? 0} online`);
            setCard('card-vlan', ov.vlans_total, `${ov.vlans_active ?? 0} active`);
            setCard('card-upl', `${ov.uplinks_up ?? 0}/${ov.uplinks_total ?? 0}`);
            setCard('card-err', Number(ov.errors_total || 0).toLocaleString());
            setCard('card-port', `${ov.port_usage ?? 0}%`, `${ov.interfaces_up ?? 0} links up`);
            setCard('card-lat', ov.latency_ms != null ? `${ov.latency_ms} ms` : '—');
            const big = document.getElementById('portUsageBig');
            const bar = document.getElementById('portUsageBar');
            const hint = document.getElementById('portUsageHint');
            if (big) big.textContent = `${ov.port_usage ?? 0}%`;
            if (bar) bar.style.width = `${Math.min(100, Number(ov.port_usage || 0))}%`;
            if (hint) hint.textContent = `${ov.interfaces_up ?? 0} links up of ${ov.interfaces_total ?? 0} interfaces`;
            if (badge) {
                badge.innerHTML = '<span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>Live · updated ' + new Date().toLocaleTimeString();
            }
        } catch (e) {
            // ignore transient network errors during live refresh
        } finally {
            if (badge) badge.classList.remove('opacity-70');
        }
    }

    if (liveEnabled) {
        setInterval(refreshOverview, refreshMs);
        // First tick shortly after open so numbers match agent without waiting a full minute.
        setTimeout(refreshOverview, 5000);
    }
});
</script>
@endpush
