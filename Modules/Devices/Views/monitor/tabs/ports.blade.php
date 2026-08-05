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
    $rows = $device->isOlt() ? $accessPorts : $device->interfaces;
@endphp

<div class="sgr-card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="font-semibold">{{ $device->isOlt() ? 'Uplink / Access Ports' : 'Fabric Interfaces' }}</h3>
            <p class="text-xs text-slate-400">{{ $rows->count() }} ports from last SNMP poll</p>
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
                <th>Errors</th>
                <th>Uplink</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($rows->when(request('q'), fn($c) => $c->filter(fn($i) => str_contains(strtolower($i->name.' '.$i->description), strtolower(request('q'))))) as $iface)
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
                </tr>
            @empty
                <tr><td colspan="8" class="px-5 py-10 text-center text-slate-400">No interface rows yet. Sync this device to pull IF-MIB data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
