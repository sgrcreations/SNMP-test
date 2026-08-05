<div class="sgr-card overflow-hidden">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="font-semibold">Status Logs</h3>
            <p class="text-xs text-slate-400">Live timeline from polls, reachability changes and system events</p>
        </div>
        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase text-slate-600">{{ $statusEvents->count() }} events</span>
    </div>

    <div class="divide-y divide-slate-100">
        @forelse($statusEvents as $event)
            <div class="flex gap-4 px-5 py-4">
                <div class="pt-1">
                    <span @class([
                        'inline-flex h-3 w-3 rounded-full ring-4 ring-white',
                        'bg-emerald-500' => $event->severity === 'success',
                        'bg-rose-500' => $event->severity === 'critical',
                        'bg-amber-500' => $event->severity === 'warning',
                        'bg-sky-500' => ! in_array($event->severity, ['success','critical','warning'], true),
                    ])></span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="font-semibold text-slate-900">{{ $event->title }}</div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ $event->category }}</span>
                    </div>
                    @if($event->message)
                        <p class="mt-1 text-sm text-slate-500">{{ $event->message }}</p>
                    @endif
                    <div class="mt-1 text-xs text-slate-400">{{ $event->occurred_at?->toDateTimeString() }} · {{ $event->occurred_at?->diffForHumans() }}</div>
                </div>
            </div>
        @empty
            <div class="px-5 py-12 text-center text-slate-400">
                No status events yet. Run Sync to create the first poll log entry.
            </div>
        @endforelse
    </div>

    @if($openAlerts->isNotEmpty())
        <div class="border-t border-slate-100 bg-rose-50/40 px-5 py-4">
            <div class="mb-2 text-xs font-bold uppercase tracking-wide text-rose-700">Related open alarms</div>
            <div class="space-y-2">
                @foreach($openAlerts->take(5) as $alert)
                    <div class="text-sm">
                        <span class="font-semibold text-rose-800">{{ $alert->title }}</span>
                        <span class="text-rose-600/80"> — {{ $alert->message }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
