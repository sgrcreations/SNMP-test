<div class="grid gap-4 xl:grid-cols-3">
    <div class="sgr-card p-4 xl:col-span-2 overflow-hidden">
        <h3 class="mb-3 font-semibold">Recent Poll Runs</h3>
        <div class="overflow-x-auto">
            <table class="sgr-table min-w-full">
                <thead class="bg-slate-50/80">
                <tr>
                    <th>Started</th>
                    <th>Result</th>
                    <th>Duration</th>
                    <th>Interfaces</th>
                    <th>Message</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($pollLogs as $log)
                    <tr>
                        <td>{{ $log->started_at?->format('Y-m-d H:i:s') }}</td>
                        <td>
                            <span class="rounded-full px-2 py-1 text-[11px] font-bold uppercase {{ $log->success ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ $log->success ? 'OK' : 'FAIL' }}
                            </span>
                        </td>
                        <td>{{ $log->duration_ms }} ms</td>
                        <td>{{ $log->interfaces_count }}</td>
                        <td class="max-w-md truncate text-xs text-slate-500">{{ $log->message }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No poll logs yet. Click Sync.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="sgr-card p-4">
        <h3 class="font-semibold">Polling Profile</h3>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-400">Interval</dt><dd class="font-semibold">{{ $device->polling_interval }}s</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Last Polled</dt><dd class="font-semibold">{{ $device->last_polled_at?->toDateTimeString() ?: 'Never' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Last Seen</dt><dd class="font-semibold">{{ $device->last_seen_at?->toDateTimeString() ?: 'Never' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Reachability</dt><dd class="font-semibold">{{ $device->reachability?->label() }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Success (last 30)</dt><dd class="font-semibold text-emerald-600">{{ $pollLogs->where('success', true)->count() }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Failed (last 30)</dt><dd class="font-semibold text-rose-600">{{ $pollLogs->where('success', false)->count() }}</dd></div>
        </dl>
        <form method="POST" action="{{ route('devices.poll', $device) }}" class="mt-5">
            @csrf
            <button class="sgr-btn-primary w-full">Run Sync Now</button>
        </form>
    </div>
</div>
