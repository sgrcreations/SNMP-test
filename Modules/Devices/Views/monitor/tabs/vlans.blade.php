<div class="sgr-card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="font-semibold">VLAN Details</h3>
            <p class="text-xs text-slate-400">
                {{ $overview['vlans_active'] }} active · {{ $overview['vlans_total'] }} total from Q-BRIDGE-MIB / IF-MIB
            </p>
        </div>
        <form method="GET" class="w-full max-w-xs sm:w-auto">
            <input type="hidden" name="tab" value="vlans">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search VLANs..." class="sgr-input">
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="sgr-table min-w-full text-left">
            <thead class="bg-slate-50/80">
            <tr>
                <th>VLAN ID</th>
                <th>Name</th>
                <th>Status</th>
                <th>Member Ports</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($vlans->when(request('q'), fn($c) => $c->filter(fn($v) => str_contains(strtolower($v->vlan_id.' '.$v->name), strtolower(request('q'))))) as $vlan)
                <tr class="hover:bg-slate-50/70">
                    <td class="font-mono font-bold text-cyan-700">{{ $vlan->vlan_id }}</td>
                    <td class="font-semibold">{{ $vlan->name ?: ('VLAN '.$vlan->vlan_id) }}</td>
                    <td>
                        <span @class([
                            'rounded-full px-2.5 py-1 text-[11px] font-bold uppercase',
                            'bg-emerald-50 text-emerald-700' => $vlan->status === 'active',
                            'bg-slate-100 text-slate-600' => $vlan->status !== 'active',
                        ])>{{ $vlan->status }}</span>
                    </td>
                    <td>{{ $vlan->member_ports }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-5 py-10 text-center text-slate-400">
                        No VLANs discovered from this device yet. Subinterfaces (e.g. Ge0/0/1.100) and Q-BRIDGE-MIB are used after Sync.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
