@php
    $q = strtolower((string) request('q'));
    $filtered = $vlans->when($q !== '', fn ($c) => $c->filter(
        fn ($v) => str_contains(strtolower($v->vlan_id.' '.$v->name), $q)
    ));
    $active = $filtered->where('status', 'active');
    $inactive = $filtered->where('status', '!=', 'active');
    $largest = $filtered->sortByDesc('member_ports')->take(5);

    // Match discovered subinterfaces that look like .vlanId (e.g. Eth0/0/1.100)
    $ifaceByVlan = $device->interfaces->groupBy(function ($iface) {
        if (preg_match('/\.(\d+)$/', (string) $iface->name, $m)) {
            return (int) $m[1];
        }

        return null;
    })->filter(fn ($_, $key) => $key !== null && $key !== '');
@endphp

<div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div class="sgr-card p-4">
        <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">VLANs Total</div>
        <div class="mt-2 text-2xl font-bold">{{ $overview['vlans_total'] }}</div>
        <p class="mt-1 text-xs text-slate-400">From Q-BRIDGE-MIB / IF-MIB</p>
    </div>
    <div class="sgr-card p-4">
        <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Active</div>
        <div class="mt-2 text-2xl font-bold text-emerald-600">{{ $overview['vlans_active'] }}</div>
        <p class="mt-1 text-xs text-slate-400">Operational VLAN rows</p>
    </div>
    <div class="sgr-card p-4">
        <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Inactive</div>
        <div class="mt-2 text-2xl font-bold text-slate-600">{{ max(0, $overview['vlans_total'] - $overview['vlans_active']) }}</div>
        <p class="mt-1 text-xs text-slate-400">Not marked active</p>
    </div>
    <div class="sgr-card p-4">
        <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Largest</div>
        <div class="mt-2 text-2xl font-bold text-cyan-700">
            {{ $largest->first()?->member_ports ?? 0 }}
        </div>
        <p class="mt-1 text-xs text-slate-400 truncate">
            {{ $largest->first() ? ('VLAN '.$largest->first()->vlan_id.' · '.($largest->first()->name ?: 'unnamed')) : 'No members' }}
        </p>
    </div>
</div>

@if($largest->isNotEmpty())
    <div class="mb-4 sgr-card p-4">
        <h3 class="mb-3 text-sm font-semibold text-slate-800">Largest by member ports</h3>
        <div class="space-y-2">
            @php $maxMembers = max(1, (int) $largest->max('member_ports')); @endphp
            @foreach($largest as $vlan)
                <div>
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="font-semibold text-slate-700">VLAN {{ $vlan->vlan_id }} · {{ $vlan->name ?: 'unnamed' }}</span>
                        <span class="font-bold text-cyan-700">{{ $vlan->member_ports }} ports</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-cyan-500" style="width: {{ round(($vlan->member_ports / $maxMembers) * 100) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="sgr-card overflow-hidden" x-data="{ openId: null }">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="font-semibold">Virtual Interfaces · VLAN Fabric</h3>
            <p class="text-xs text-slate-400">
                {{ $filtered->count() }} shown · expand a row for matched subinterfaces on this device
            </p>
        </div>
        <form method="GET" class="w-full max-w-xs sm:w-auto">
            <input type="hidden" name="tab" value="vlans">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search VLANs..." class="sgr-input">
        </form>
    </div>

    <div class="divide-y divide-slate-100">
        @forelse($filtered->sortBy('vlan_id') as $vlan)
            @php
                $related = $ifaceByVlan->get((int) $vlan->vlan_id, collect());
                $rx = $related->sum(fn ($i) => (float) ($i->rx_bps ?? 0));
                $tx = $related->sum(fn ($i) => (float) ($i->tx_bps ?? 0));
                $fmt = function (float $bps): string {
                    if ($bps >= 1_000_000) return number_format($bps / 1_000_000, 2).' Mbps';
                    if ($bps >= 1_000) return number_format($bps / 1_000, 2).' Kbps';
                    return number_format($bps, 0).' bps';
                };
            @endphp
            <div class="px-5 py-4">
                <button
                    type="button"
                    class="flex w-full flex-wrap items-center justify-between gap-3 text-left"
                    @click="openId = openId === {{ $vlan->id }} ? null : {{ $vlan->id }}"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-lg font-bold text-cyan-700">VLAN {{ $vlan->vlan_id }}</span>
                            <span class="font-semibold text-slate-800">{{ $vlan->name ?: 'unnamed' }}</span>
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                                'bg-emerald-50 text-emerald-700' => $vlan->status === 'active',
                                'bg-slate-100 text-slate-600' => $vlan->status !== 'active',
                            ])>{{ $vlan->status }}</span>
                        </div>
                        <div class="mt-1 text-xs text-slate-400">
                            {{ $vlan->member_ports }} member port{{ $vlan->member_ports === 1 ? '' : 's' }}
                            · {{ $related->count() }} subinterface{{ $related->count() === 1 ? '' : 's' }} matched on this device
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <div class="text-right">
                            <div class="text-[10px] font-bold uppercase text-slate-400">IN</div>
                            <div class="font-semibold text-blue-600">↓ {{ $fmt($rx) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] font-bold uppercase text-slate-400">OUT</div>
                            <div class="font-semibold text-emerald-600">↑ {{ $fmt($tx) }}</div>
                        </div>
                        <svg class="h-4 w-4 text-slate-400 transition" :class="openId === {{ $vlan->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>

                <div x-show="openId === {{ $vlan->id }}" x-cloak class="mt-4 grid gap-3 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm">
                        <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Logical metadata</div>
                        <dl class="mt-3 grid grid-cols-2 gap-2">
                            <div><dt class="text-xs text-slate-400">VLAN ID</dt><dd class="font-semibold">{{ $vlan->vlan_id }}</dd></div>
                            <div><dt class="text-xs text-slate-400">Status</dt><dd class="font-semibold uppercase">{{ $vlan->status }}</dd></div>
                            <div><dt class="text-xs text-slate-400">Name</dt><dd class="font-semibold">{{ $vlan->name ?: '—' }}</dd></div>
                            <div><dt class="text-xs text-slate-400">Member ports</dt><dd class="font-semibold">{{ $vlan->member_ports }}</dd></div>
                        </dl>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-white p-4 text-sm">
                        <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Matched subinterfaces</div>
                        @if($related->isEmpty())
                            <p class="mt-3 text-slate-400">No IF-MIB names ending in .{{ $vlan->vlan_id }} on this device. Member count still comes from VLAN MIB.</p>
                        @else
                            <ul class="mt-3 max-h-48 space-y-2 overflow-y-auto">
                                @foreach($related as $iface)
                                    <li class="flex items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2">
                                        <div class="min-w-0">
                                            <div class="truncate font-semibold">{{ $iface->name }}</div>
                                            <div class="truncate text-xs text-slate-400">{{ $iface->oper_status }} · ifIndex {{ $iface->if_index }}</div>
                                        </div>
                                        <div class="shrink-0 text-right text-xs">
                                            <div class="text-blue-600">↓ {{ $fmt((float) ($iface->rx_bps ?? 0)) }}</div>
                                            <div class="text-emerald-600">↑ {{ $fmt((float) ($iface->tx_bps ?? 0)) }}</div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="px-5 py-10 text-center text-slate-400">
                No VLANs discovered yet. Sync this device — VLANs come from Q-BRIDGE-MIB (via snmp-agent) and IF names (Vlanif / .tag).
            </div>
        @endforelse
    </div>
</div>
