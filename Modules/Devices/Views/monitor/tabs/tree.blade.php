@php
    $treePorts = $ponPorts->values();
    $cols = min(4, max(1, $treePorts->count()));
@endphp

<div class="sgr-card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="font-semibold">Network Tree</h3>
            <p class="text-xs text-slate-400">OLT → PON → ONU hierarchy from live SNMP inventory</p>
        </div>
        <div class="flex items-center gap-2">
            @if($openAlerts->count() > 0)
                <a href="{{ route('devices.show', [$device, 'tab' => 'alarms']) }}" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold uppercase tracking-wide text-amber-700">
                    {{ $openAlerts->count() }} need attention →
                </a>
            @endif
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase text-emerald-700">
                {{ $overview['onu_online'] }}/{{ $overview['onu_total'] }} ONUs online
            </span>
        </div>
    </div>

    <div class="bg-[radial-gradient(circle_at_top,#ecfeff_0%,#f8fafc_45%,#ffffff_100%)] p-5">
        <div class="overflow-x-auto pb-2">
            <div class="min-w-[900px] space-y-0">
                <div class="flex justify-center">
                    <div class="relative rounded-2xl border-2 border-cyan-300 bg-white px-8 py-5 text-center shadow-md">
                        <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-cyan-700 text-white shadow">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="text-xl font-bold tracking-tight">{{ $device->name }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $device->ip_address }}</div>
                        <div class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold uppercase text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            {{ strtoupper($device->reachability?->value ?? 'unknown') }}
                        </div>
                    </div>
                </div>

                @if($treePorts->isEmpty())
                    <div class="mt-8 rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-12 text-center text-sm text-slate-400">
                        No PON branches yet. Poll an OLT so IF-MIB ports named PON/GPON and ONU inventory can build this tree.
                    </div>
                @else
                    <div class="mx-auto my-2 h-8 w-px bg-slate-300"></div>
                    <div class="relative mx-auto mb-2 hidden h-px bg-slate-300 sm:block" style="width: {{ max(20, ($cols - 1) * 22) }}%"></div>

                    <div class="grid gap-4" style="grid-template-columns: repeat({{ $cols }}, minmax(210px, 1fr));">
                        @foreach($treePorts as $pon)
                            @php
                                $ponOnus = $onus->where('pon_port', $pon->name)->values();
                                $online = (int) $pon->onu_online;
                                $total = (int) $pon->onu_total;
                                $offline = max(0, $total - $online);
                                $low = $ponOnus->where('status', 'low_signal')->count();
                                $health = $total > 0 ? round(($online / $total) * 100) : ($pon->oper_status === 'up' ? 100 : 0);
                            @endphp
                            <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                                <div class="mb-1 flex items-center justify-between gap-2">
                                    <div class="truncate font-bold text-slate-900">{{ $pon->name }}</div>
                                    <div class="shrink-0 text-[11px] font-semibold text-slate-400">{{ $total }} ONUs</div>
                                </div>
                                <div class="mb-2 text-[11px] leading-relaxed text-slate-500">
                                    <span class="text-emerald-600 font-semibold">{{ $online }} online</span>
                                    · <span class="text-rose-600 font-semibold">{{ $offline }} offline</span>
                                    @if($low > 0)
                                        · <span class="text-amber-600 font-semibold">{{ $low }} low signal</span>
                                    @endif
                                </div>
                                <div class="mb-1 flex items-center justify-between text-[11px]">
                                    <span class="text-slate-400">Health</span>
                                    <span class="font-bold {{ $health >= 90 ? 'text-emerald-600' : ($health >= 70 ? 'text-amber-600' : 'text-rose-600') }}">{{ $health }}%</span>
                                </div>
                                <div class="mb-3 h-2.5 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full transition-all {{ $health >= 90 ? 'bg-emerald-500' : ($health >= 70 ? 'bg-amber-400' : 'bg-rose-500') }}" style="width: {{ $health }}%"></div>
                                </div>
                                <div class="grid max-h-64 grid-cols-3 gap-1.5 overflow-y-auto sm:grid-cols-4">
                                    @forelse($ponOnus->take(48) as $onu)
                                        <div @class([
                                            'rounded-lg border px-1 py-1.5 text-center',
                                            'border-emerald-200 bg-emerald-50' => $onu->status === 'online',
                                            'border-rose-200 bg-rose-50' => $onu->status === 'offline',
                                            'border-amber-200 bg-amber-50' => $onu->status === 'low_signal',
                                            'border-slate-200 bg-slate-50' => !in_array($onu->status, ['online','offline','low_signal'], true),
                                        ]) title="{{ $onu->serial }}">
                                            <div class="truncate text-[10px] font-bold leading-tight">ONU {{ $onu->onu_id ?? substr((string)$onu->serial, -3) }}</div>
                                            <div @class([
                                                'mx-auto mt-1 h-1.5 w-1.5 rounded-full',
                                                'bg-emerald-500' => $onu->status === 'online',
                                                'bg-rose-500' => $onu->status === 'offline',
                                                'bg-amber-500' => $onu->status === 'low_signal',
                                                'bg-slate-400' => !in_array($onu->status, ['online','offline','low_signal'], true),
                                            ])></div>
                                        </div>
                                    @empty
                                        <div class="col-span-full rounded-lg border border-dashed border-slate-200 py-6 text-center text-[11px] text-slate-400">
                                            No ONU cards under this PON yet
                                        </div>
                                    @endforelse
                                </div>
                                @if($ponOnus->count() > 48)
                                    <div class="mt-2 text-center text-[10px] text-slate-400">+{{ $ponOnus->count() - 48 }} more — see ONUs tab</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-center gap-5 border-t border-slate-100 pt-4 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Online</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span> Offline</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span> Low signal</span>
        </div>
    </div>
</div>
