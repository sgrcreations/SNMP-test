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
@endphp

<div class="sgr-card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="font-semibold">PON Ports</h3>
            <p class="text-xs text-slate-400">
                <span class="mr-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span> Live
                </span>
                {{ $ponPorts->count() }} ports found
            </p>
        </div>
        <form method="GET" class="w-full max-w-xs sm:w-auto">
            <input type="hidden" name="tab" value="pon">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search ports..." class="sgr-input">
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="sgr-table min-w-full text-left">
            <thead class="bg-slate-50/80">
            <tr>
                <th>Port</th>
                <th>Description</th>
                <th>Status</th>
                <th>ONUs</th>
                <th>Down %</th>
                <th>Traffic</th>
                <th>TX Power</th>
                <th>Util</th>
                <th>Temp</th>
                <th class="text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($ponPorts->when(request('q'), fn($c) => $c->filter(fn($i) => str_contains(strtolower($i->name.' '.$i->description), strtolower(request('q'))))) as $pon)
                @php
                    $downPct = $pon->onu_total > 0
                        ? round((($pon->onu_total - $pon->onu_online) / $pon->onu_total) * 100, 1)
                        : 0;
                @endphp
                <tr class="hover:bg-slate-50/70">
                    <td class="font-semibold">{{ $pon->name }}</td>
                    <td class="text-sm text-slate-500">{{ $pon->description ?: $pon->name }}</td>
                    <td>
                        <span @class([
                            'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold uppercase',
                            'bg-emerald-50 text-emerald-700' => $pon->oper_status === 'up',
                            'bg-rose-50 text-rose-700' => $pon->oper_status !== 'up',
                        ])>
                            {{ strtoupper($pon->oper_status === 'up' ? 'UP' : 'DOWN') }}
                        </span>
                    </td>
                    <td>
                        <span class="font-semibold text-sky-600">{{ $pon->onu_online }}</span>
                        <span class="text-slate-400"> / {{ $pon->onu_total }}</span>
                    </td>
                    <td @class([
                        'font-semibold',
                        'text-rose-600' => $downPct >= 15,
                        'text-amber-600' => $downPct >= 5 && $downPct < 15,
                        'text-emerald-600' => $downPct < 5,
                    ])>{{ number_format($downPct, 1) }}%</td>
                    <td class="text-sm">
                        <div class="text-emerald-600">↓ {{ $fmtBps($pon->rx_bps) }}</div>
                        <div class="text-sky-600">↑ {{ $fmtBps($pon->tx_bps) }}</div>
                    </td>
                    <td class="text-sm text-violet-600">
                        {{ $pon->tx_power_dbm !== null ? number_format((float) $pon->tx_power_dbm, 1).' dBm' : '—' }}
                    </td>
                    <td class="font-semibold text-emerald-600">{{ $pon->utilization !== null ? $pon->utilization.'%' : '—' }}</td>
                    <td>{{ $pon->temperature !== null ? $pon->temperature.'°C' : '—' }}</td>
                    <td class="text-right">
                        <a href="{{ route('devices.show', ['device' => $device, 'tab' => 'onus', 'pon' => $pon->name]) }}"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-700"
                           title="View ONUs on {{ $pon->name }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-5 py-10 text-center text-slate-400">
                        No PON ports found yet. After SNMP polls a GPON OLT, ports named PON/GPON appear here. Sync now to refresh.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
