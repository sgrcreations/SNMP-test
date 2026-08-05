<div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
    @foreach([
        ['Online ONUs', $overview['onu_online'], 'text-emerald-600'],
        ['Offline ONUs', $overview['onu_offline'], 'text-rose-600'],
        ['PON Up', $overview['pon_up'].'/'.$overview['pon_total'], 'text-cyan-600'],
        ['Uplinks Up', $overview['uplinks_up'].'/'.$overview['uplinks_total'], 'text-violet-600'],
        ['Active Alarms', $overview['open_alerts'], 'text-amber-600'],
    ] as [$label, $value, $color])
        <div class="sgr-card p-4">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ $label }}</div>
            <div class="mt-2 text-3xl font-bold {{ $color }}">{{ $value }}</div>
        </div>
    @endforeach
</div>

<div class="mb-4 grid gap-4 xl:grid-cols-3">
    <div class="sgr-card p-4 xl:col-span-2">
        <h3 class="mb-4 font-semibold">Access Network Map</h3>
        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
            <div class="mb-4 flex flex-col items-center gap-2">
                <div class="rounded-2xl border border-emerald-200 bg-white px-4 py-2 text-center shadow-sm">
                    <div class="text-[10px] font-bold uppercase text-slate-400">Internet</div>
                    <div class="text-sm font-semibold">{{ $overview['uplinks_up'] }} active uplink{{ $overview['uplinks_up'] === 1 ? '' : 's' }}</div>
                </div>
                <div class="h-6 w-px bg-slate-300"></div>
                <div class="rounded-2xl border border-cyan-200 bg-white px-4 py-3 text-center shadow-sm">
                    <div class="text-xs font-bold uppercase text-slate-400">OLT</div>
                    <div class="font-semibold">{{ $device->name }}</div>
                    <div class="text-xs text-slate-500">{{ $device->ip_address }} · {{ $device->reachability?->label() }}</div>
                </div>
                <div class="h-6 w-px bg-slate-300"></div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($ponPorts as $pon)
                    @php
                        $health = $pon->onu_total > 0 ? round(($pon->onu_online / $pon->onu_total) * 100) : ($pon->oper_status === 'up' ? 100 : 0);
                        $attention = $pon->oper_status !== 'up' || ($pon->onu_total === 0);
                    @endphp
                    <div @class([
                        'rounded-2xl border bg-white p-3',
                        'border-emerald-200' => ! $attention && $health >= 80,
                        'border-amber-300' => $attention || $health < 80,
                        'border-rose-200' => $pon->oper_status !== 'up',
                    ])>
                        <div class="flex items-center justify-between">
                            <div class="font-semibold">{{ $pon->name }}</div>
                            <span class="text-[10px] font-bold uppercase {{ $pon->oper_status === 'up' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $pon->oper_status === 'up' ? 'Up' : 'Down' }}
                            </span>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">
                            @if($attention && $pon->onu_total === 0)
                                Needs attention · 0 ONUs
                            @else
                                {{ $pon->onu_online }} / {{ $pon->onu_total }} ONUs
                            @endif
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $health >= 80 ? 'bg-emerald-500' : ($health >= 50 ? 'bg-amber-400' : 'bg-rose-500') }}" style="width: {{ $health }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-400">
                        No PON ports detected from IF-MIB (names containing PON/GPON). Sync an OLT device to populate this map.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="sgr-card p-4">
            <h3 class="font-semibold">System Health</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-slate-400">CPU</dt><dd class="font-bold">{{ $overview['cpu'] !== null ? $overview['cpu'].'%' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Memory</dt><dd class="font-bold">{{ $overview['memory'] !== null ? $overview['memory'].'%' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Temp</dt><dd class="font-bold">{{ $overview['temperature'] !== null ? $overview['temperature'].'°C' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Latency</dt><dd class="font-bold">{{ $overview['latency_ms'] !== null ? $overview['latency_ms'].' ms' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Uptime</dt><dd class="font-bold text-amber-600">{{ $device->sys_uptime ?: '—' }}</dd></div>
            </dl>
        </div>
        <div class="sgr-card p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold">Subscriber Availability</h3>
                <div class="flex gap-1">
                    @foreach(['1d','3d','7d'] as $r)
                        <a href="{{ route('devices.show', ['device' => $device, 'tab' => 'command-center', 'range' => $r]) }}"
                           class="rounded-lg px-2 py-1 text-[10px] font-bold {{ $range === $r ? 'bg-cyan-50 text-cyan-700' : 'text-slate-400' }}">{{ strtoupper($r) }}</a>
                    @endforeach
                </div>
            </div>
            <div id="onuAvailChart" class="h-44"></div>
            @if(count($onuAvailabilitySeries['categories']) === 0)
                <p class="mt-2 text-xs text-slate-400">ONU totals are stored each successful OLT poll. Sync again after SNMP returns ONU inventory.</p>
            @endif
        </div>
    </div>
</div>

<div class="grid gap-4 xl:grid-cols-2">
    <div class="sgr-card p-4">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold">Device Uplink Traffic</h3>
            <span class="text-[10px] font-bold uppercase text-slate-400">Mbps</span>
        </div>
        <div id="oltTrafficChart" class="h-56"></div>
        @if(empty($trafficSeries['mapped']) || count($trafficSeries['categories']) === 0)
            <p class="mt-2 text-xs text-slate-400">Uplink is auto-selected from physical ports. Sync twice to chart rates.</p>
        @endif
    </div>
    <div class="sgr-card p-4">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold">Network Quality</h3>
            <div class="flex gap-1">
                @foreach(['6h','24h','7d'] as $r)
                    <a href="{{ route('devices.show', ['device' => $device, 'tab' => 'command-center', 'range' => $r]) }}"
                       class="rounded-lg px-2 py-1 text-[10px] font-bold {{ $range === $r ? 'bg-cyan-50 text-cyan-700' : 'text-slate-400' }}">{{ strtoupper($r) }}</a>
                @endforeach
            </div>
        </div>
        <div id="oltQualityChart" class="h-56"></div>
        @if(count($qualitySeries['categories']) === 0)
            <p class="mt-2 text-xs text-slate-400">No ping samples yet — Sync stores real ICMP latency, jitter and loss.</p>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const traffic = @json($trafficSeries);
    const quality = @json($qualitySeries);
    const onuAvail = @json($onuAvailabilitySeries);

    if (window.ApexCharts && traffic.categories?.length) {
        new ApexCharts(document.querySelector('#oltTrafficChart'), {
            chart: { type: 'area', height: 220, toolbar: { show: false } },
            stroke: { curve: 'smooth', width: 2 },
            colors: ['#2563eb', '#059669'],
            series: [
                { name: 'RX Mbps', data: traffic.rx_mbps },
                { name: 'TX Mbps', data: traffic.tx_mbps },
            ],
            xaxis: { categories: traffic.categories, labels: { show: false } },
            dataLabels: { enabled: false },
            legend: { show: true, position: 'top' },
        }).render();
    }

    if (window.ApexCharts && quality.categories?.length) {
        new ApexCharts(document.querySelector('#oltQualityChart'), {
            chart: { type: 'line', height: 220, toolbar: { show: false } },
            stroke: { curve: 'smooth', width: 2 },
            colors: ['#2563eb', '#f59e0b', '#e11d48'],
            series: [
                { name: 'Latency ms', data: quality.latency },
                { name: 'Jitter ms', data: quality.jitter },
                { name: 'Loss %', data: quality.loss },
            ],
            xaxis: { categories: quality.categories, labels: { show: false } },
            dataLabels: { enabled: false },
            legend: { position: 'top' },
        }).render();
    }

    if (window.ApexCharts && onuAvail.categories?.length) {
        new ApexCharts(document.querySelector('#onuAvailChart'), {
            chart: { type: 'area', height: 180, toolbar: { show: false } },
            stroke: { curve: 'smooth', width: 2 },
            colors: ['#059669'],
            series: [{ name: 'Availability %', data: onuAvail.availability }],
            xaxis: { categories: onuAvail.categories, labels: { show: false } },
            dataLabels: { enabled: false },
            yaxis: { min: 0, max: 100 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        }).render();
    }
});
</script>
@endpush
