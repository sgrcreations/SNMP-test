@php
    $cards = [
        ['Interfaces', $overview['interfaces_total'], $overview['interfaces_up'].' online', 'emerald'],
        ['VLANs', $overview['vlans_total'], $overview['vlans_active'].' active', 'sky'],
        ['CPU Load', ($overview['cpu'] ?? '—').($overview['cpu'] !== null ? '%' : ''), 'System', 'cyan'],
        ['Memory', ($overview['memory'] ?? '—').($overview['memory'] !== null ? '%' : ''), 'RAM', 'violet'],
        ['Temperature', ($overview['temperature'] ?? '—').($overview['temperature'] !== null ? '°C' : ''), 'Thermal', 'amber'],
        ['Latency', ($overview['latency_ms'] ?? '—').($overview['latency_ms'] !== null ? ' ms' : ''), 'ICMP', 'rose'],
    ];
@endphp

<div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
    @foreach($cards as [$label, $value, $sub, $tone])
        <div class="sgr-card p-4">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ $label }}</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $value }}</div>
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
        @if(count($metricSeries['categories']) === 0)
            <p class="mt-2 text-sm text-slate-400">No metric samples yet for this range. Run Sync to collect data.</p>
        @endif
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
            <p class="mt-2 text-sm text-slate-400">No ping samples for this range. Sync writes real ICMP latency / jitter / loss.</p>
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
    const metrics = @json($metricSeries);
    const traffic = @json($trafficSeries);
    const quality = @json($qualitySeries);

    if (window.ApexCharts && metrics.categories.length) {
        new ApexCharts(document.querySelector('#metricsChart'), {
            chart: { type: 'area', height: 280, toolbar: { show: false }, animations: { enabled: true } },
            stroke: { curve: 'smooth', width: 2 },
            dataLabels: { enabled: false },
            colors: ['#0891b2', '#7c3aed', '#f59e0b'],
            series: [
                { name: 'CPU %', data: metrics.cpu },
                { name: 'Memory %', data: metrics.memory },
                { name: 'Temp °C', data: metrics.temperature },
            ],
            xaxis: { categories: metrics.categories, labels: { style: { colors: '#94a3b8' } } },
            yaxis: { labels: { style: { colors: '#94a3b8' } } },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
            grid: { borderColor: '#e2e8f0' },
            legend: { position: 'top' },
        }).render();
    }

    if (window.ApexCharts && traffic.categories?.length) {
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

    if (window.ApexCharts && quality.categories?.length) {
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
});
</script>
@endpush
