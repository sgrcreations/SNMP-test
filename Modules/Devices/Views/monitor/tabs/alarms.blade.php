<div class="sgr-card overflow-hidden">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="font-semibold">Alarms</h3>
            <p class="text-xs text-slate-400">Generated from live poll thresholds and interface state</p>
        </div>
        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-bold uppercase text-rose-700">{{ $openAlerts->count() }} open</span>
    </div>
    <div class="overflow-x-auto">
        <table class="sgr-table min-w-full">
            <thead class="bg-slate-50/80">
            <tr>
                <th>Severity</th>
                <th>Type</th>
                <th>Title</th>
                <th>Status</th>
                <th>Raised</th>
                <th>Message</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($alerts as $alert)
                <tr>
                    <td>
                        <span @class([
                            'rounded-full px-2 py-1 text-[11px] font-bold uppercase',
                            'bg-rose-50 text-rose-700' => $alert->severity === 'critical',
                            'bg-amber-50 text-amber-700' => $alert->severity === 'warning',
                            'bg-slate-100 text-slate-600' => !in_array($alert->severity, ['critical','warning'], true),
                        ])>{{ $alert->severity }}</span>
                    </td>
                    <td class="font-mono text-xs">{{ $alert->type }}</td>
                    <td class="font-semibold">{{ $alert->title }}</td>
                    <td class="uppercase text-xs font-bold">{{ $alert->status }}</td>
                    <td>{{ $alert->raised_at?->diffForHumans() }}</td>
                    <td class="text-xs text-slate-500">{{ $alert->message }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">No alarms recorded for this device.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
