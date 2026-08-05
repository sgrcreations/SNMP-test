@php
    $cards = [
        ['Interfaces', $overview['interfaces_total'], $overview['interfaces_up'].' online', 'emerald', 'card-if'],
        ['VLANs', $overview['vlans_total'], $overview['vlans_active'].' active', 'sky', 'card-vlan'],
        ['CPU Load', ($overview['cpu'] ?? '—').($overview['cpu'] !== null ? '%' : ''), 'System', 'cyan', 'card-cpu'],
        ['Memory', ($overview['memory'] ?? '—').($overview['memory'] !== null ? '%' : ''), 'RAM', 'violet', 'card-mem'],
        ['Temperature', ($overview['temperature'] ?? '—').($overview['temperature'] !== null ? '°C' : ''), 'Thermal', 'amber', 'card-temp'],
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
            <div class="mt-1 text-xs text-slate-400">{{ $sub }}</div>
        </div>
    @endforeach
</div>

<div class="mb-4 grid gap-4 xl:grid-cols-3">
    <div class="sgr-card p-4 xl:col-span-2">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold">CPU / Memory / Temperature</h3>
            <div class="flex gap-1">
                @foreach(['1h','24h','7d','30d'] as $r)
                    <a href="{{ route('devices.show', ['device' => $device, 'tab' => 'overview', 'range' => $r]) }}"
                       class="rounded-lg px-2 py-1 text-[11px] font-bold {{ $range === $r ? 'bg-cyan-50 text-cyan-700' : 'text-slate-400 hover:bg-slate-50' }}">{{ strtoupper($r) }}</a>
                @endforeach
            </div>
        </div>
        <div id="metricsChart" class="h-72"></div>
        <p id="metricsEmpty" class="mt-2 text-sm text-slate-400 {{ count($metricSeries['categories']) === 0 ? '' : 'hidden' }}">
            No metric samples yet for this range. Agent polls {{ $profile['interval_label'] }} — keep this page open for live updates, or click Sync.
        </p>
    </div>

    <div class="sgr-card p-4">
        <h3 class="font-semibold">Hardware Identity</h3>
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
            <h3 class="font-semibold">Device Uplink Traffic</h3>
            <span class="text-[10px] font-bold uppercase text-slate-400">Mbps</span>
        </div>
        <div id="trafficChart" class="h-64"></div>
        @if(empty($trafficSeries['mapped']))
            <p class="mt-2 text-sm text-slate-400">No suitable uplink interface found yet. Sync again after the device reports physical ports.</p>
        @elseif(count($trafficSeries['categories']) === 0)
            <p class="mt-2 text-sm text-slate-400">Need at least two poll samples to chart traffic rates. Click Sync again in a minute.</p>
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
        <h3 class="font-semibold">Software & Capacity</h3>
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
            <div class="text-4xl font-bold text-cyan-700">{{ $overview['port_usage'] }}%</div>
            <div class="flex-1">
                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-cyan-500" style="width: {{ min(100, $overview['port_usage']) }}%"></div>
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ $overview['interfaces_up'] }} links up of {{ $overview['interfaces_total'] }} interfaces</p>
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

    let metrics = @json($metricSeries);
    const traffic = @json($trafficSeries);
    const quality = @json($qualitySeries);
    let metricsChart = null;

    const fmt = (v, suffix) => (v === null || v === undefined || v === '') ? '—' : `${v}${suffix}`;

    if (window.ApexCharts) {
        metricsChart = new ApexCharts(document.querySelector('#metricsChart'), {
            chart: { type: 'area', height: 280, toolbar: { show: false }, animations: { enabled: true } },
            stroke: { curve: 'smooth', width: 2 },
            dataLabels: { enabled: false },
            colors: ['#0891b2', '#7c3aed', '#f59e0b'],
            series: [
                { name: 'CPU %', data: metrics.cpu || [] },
                { name: 'Memory %', data: metrics.memory || [] },
                { name: 'Temp °C', data: metrics.temperature || [] },
            ],
            xaxis: { categories: metrics.categories || [], labels: { style: { colors: '#94a3b8' } } },
            yaxis: { labels: { style: { colors: '#94a3b8' } } },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
            grid: { borderColor: '#e2e8f0' },
            legend: { position: 'top' },
            noData: { text: 'Waiting for samples…' },
        });
        metricsChart.render();

        if (traffic.categories?.length) {
            new ApexCharts(document.querySelector('#trafficChart'), {
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
            }).render();
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

    async function refreshMetrics() {
        if (document.hidden || !liveEnabled) return;
        try {
            const res = await fetch(`${metricsUrl}?range=${encodeURIComponent(range)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const payload = await res.json();
            metrics = payload.metrics || metrics;
            if (metricsChart && metrics.categories) {
                metricsChart.updateOptions({ xaxis: { categories: metrics.categories } });
                metricsChart.updateSeries([
                    { name: 'CPU %', data: metrics.cpu || [] },
                    { name: 'Memory %', data: metrics.memory || [] },
                    { name: 'Temp °C', data: metrics.temperature || [] },
                ]);
                document.getElementById('metricsEmpty')?.classList.toggle('hidden', (metrics.categories || []).length > 0);
            }
            if (payload.overview || metrics.cpu) {
                const last = (arr) => (Array.isArray(arr) && arr.length) ? arr[arr.length - 1] : null;
                const cpuEl = document.querySelector('#card-cpu .card-value');
                const memEl = document.querySelector('#card-mem .card-value');
                const tempEl = document.querySelector('#card-temp .card-value');
                if (cpuEl) cpuEl.textContent = fmt(last(metrics.cpu) ?? payload.overview?.cpu, '%');
                if (memEl) memEl.textContent = fmt(last(metrics.memory) ?? payload.overview?.memory, '%');
                if (tempEl) tempEl.textContent = fmt(last(metrics.temperature) ?? payload.overview?.temperature, '°C');
            }
        } catch (e) {
            // ignore transient network errors during live refresh
        }
    }

    if (liveEnabled) {
        setInterval(refreshMetrics, refreshMs);
    }
});
</script>
@endpush
