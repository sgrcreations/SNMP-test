<div class="sgr-card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="font-semibold">Optical Network Units (ONUs)</h3>
            <p class="text-xs text-slate-400">Showing {{ $onus->count() }} of {{ $device->onus()->count() }} metrics</p>
        </div>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="tab" value="onus">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search ONUs..." class="sgr-input w-44">
            <select name="status" class="sgr-input w-36" onchange="this.form.submit()">
                <option value="">All status</option>
                <option value="online" @selected(request('status') === 'online')>Online</option>
                <option value="offline" @selected(request('status') === 'offline')>Offline</option>
                <option value="low_signal" @selected(request('status') === 'low_signal')>Low signal</option>
            </select>
            <select name="pon" class="sgr-input w-36" onchange="this.form.submit()">
                <option value="">All ports</option>
                @foreach($ponPorts as $pon)
                    <option value="{{ $pon->name }}" @selected(request('pon') === $pon->name)>{{ $pon->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="sgr-table min-w-full text-left">
            <thead class="bg-slate-50/80">
            <tr>
                <th>Device</th>
                <th>Desc</th>
                <th>Port</th>
                <th>RX</th>
                <th>TX</th>
                <th>Dist</th>
                <th>Temp</th>
                <th>Seen</th>
                <th>Customer</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @php
                $filtered = $onus
                    ->when(request('q'), fn($c) => $c->filter(fn($o) => str_contains(strtolower(($o->serial ?? '').' '.($o->description ?? '').' '.($o->customer ?? '')), strtolower(request('q')))))
                    ->when(request('status'), fn($c) => $c->where('status', request('status')))
                    ->when(request('pon'), fn($c) => $c->where('pon_port', request('pon')));
            @endphp
            @forelse($filtered as $onu)
                @php
                    $rx = $onu->rx_power_dbm;
                    $tx = $onu->tx_power_dbm;
                    $rxBad = $rx !== null && ((float) $rx < -28 || (float) $rx > 1000);
                    $isOutlier = $onu->temperature !== null && (float) $onu->temperature > 200;
                @endphp
                <tr class="hover:bg-slate-50/70">
                    <td>
                        <div class="flex items-center gap-2">
                            <span @class([
                                'h-2.5 w-2.5 rounded-full',
                                'bg-emerald-500' => $onu->status === 'online',
                                'bg-rose-500' => $onu->status === 'offline',
                                'bg-amber-500' => $onu->status === 'low_signal',
                                'bg-slate-400' => !in_array($onu->status, ['online','offline','low_signal'], true),
                            ])></span>
                            <span class="font-mono text-sm font-semibold">{{ $onu->serial ?: ('ONU-'.$onu->onu_id) }}</span>
                        </div>
                    </td>
                    <td class="text-sm text-slate-600">{{ $onu->description ?: '—' }}</td>
                    <td>
                        <span class="rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-bold text-sky-700">{{ $onu->pon_port ?: '—' }}</span>
                    </td>
                    <td @class(['font-semibold text-sm', 'text-rose-600' => $rxBad || $isOutlier, 'text-amber-600' => !$rxBad && !$isOutlier && $rx !== null && (float)$rx < -25, 'text-emerald-600' => !$rxBad && !$isOutlier])>
                        {{ $rx !== null && !$isOutlier ? number_format((float)$rx, 2).' dBm' : '—' }}
                    </td>
                    <td class="font-semibold text-sm text-emerald-600">
                        {{ $tx !== null && !$isOutlier ? number_format((float)$tx, 2).' dBm' : '—' }}
                    </td>
                    <td>{{ $onu->distance_m ? $onu->distance_m.'m' : '—' }}</td>
                    <td>{{ $onu->temperature !== null && !$isOutlier ? number_format((float)$onu->temperature, 1).'°C' : '—' }}</td>
                    <td class="text-xs text-slate-500">{{ $onu->last_seen_at?->diffForHumans() ?: '—' }}</td>
                    <td class="text-sm">{{ $onu->customer ?: 'UNASSIGNED' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-5 py-10 text-center text-slate-400">
                        No ONU inventory from SNMP yet. Huawei OLT collectors populate this after a successful poll.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3 text-xs text-slate-400">
        <span>Green = online · Red = offline · Amber = low signal</span>
        <span>{{ $openAlerts->count() }} open device alarms</span>
    </div>
</div>
