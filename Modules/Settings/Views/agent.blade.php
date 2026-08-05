<x-monitor-layout
    title="Agent Updates"
    header="Agent Updates"
    subheader="Check and apply on-prem snmp-agent updates"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Settings', 'url' => route('settings.edit')],
        ['label' => 'Agent Updates'],
    ]"
>
    @if (!empty($error))
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $error }}
        </div>
    @endif

    <div class="space-y-6">
        <section class="sgr-card p-5">
            <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-400">Release channel</h2>
            <p class="mt-2 text-sm text-slate-600">
                Releases are published by git/CI (<code class="rounded bg-slate-100 px-1">php artisan agent:publish-release</code>), not by uploading in the browser.
            </p>

            @if (!empty($latestRelease['version']))
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    Latest published: <strong>{{ $latestRelease['version'] }}</strong>
                    <div class="mt-1 break-all font-mono text-xs">{{ $latestRelease['manifest_url'] }}</div>
                </div>
            @else
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    No release on this Laravel channel yet. After git deploy / CI, run
                    <code class="rounded bg-amber-100 px-1">php artisan agent:publish-release x.y.z /path/to/snmpd-linux-amd64 --push-channel</code>
                </div>
            @endif
        </section>

        @unless ($configured)
            <section class="sgr-card p-5">
                <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-400">Connect agent</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Set <strong>SNMP Agent URL</strong> and <strong>API Key</strong> under
                    <a href="{{ route('settings.edit') }}" class="font-semibold text-cyan-700 hover:underline">Settings → agent</a>.
                </p>
            </section>
        @else
            <div class="grid gap-6 lg:grid-cols-2">
                <section class="sgr-card p-5">
                    <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-400">Running agent</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Health</dt>
                            <dd class="font-semibold text-slate-900">{{ $health['status'] ?? 'unknown' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Version</dt>
                            <dd class="font-semibold text-slate-900">{{ $health['version'] ?? ($status['current_version'] ?? '—') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">OS / Arch</dt>
                            <dd class="font-semibold text-slate-900">{{ ($status['os'] ?? '—') }} / {{ ($status['arch'] ?? '—') }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Channel URL on agent</dt>
                            <dd class="mt-1 break-all font-mono text-xs text-slate-700">{{ $status['channel_url'] ?: 'Not set' }}</dd>
                        </div>
                        @if (!empty($status['last_error']))
                            <div>
                                <dt class="text-slate-500">Last error</dt>
                                <dd class="mt-1 text-rose-700">{{ $status['last_error'] }}</dd>
                            </div>
                        @endif
                    </dl>

                    @can('settings.update')
                        <form method="POST" action="{{ route('settings.agent.push-channel') }}" class="mt-4">
                            @csrf
                            <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Point agent at this Laravel channel
                            </button>
                        </form>
                        <p class="mt-2 break-all text-xs text-slate-500">{{ $channelUrl }}</p>
                    @endcan
                </section>

                <section class="sgr-card p-5">
                    <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-400">Update</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Check compares agent version to the published channel. Apply downloads, verifies signature, swaps binary, restarts (≈2–5s).
                    </p>

                    @if (!empty($status['update_available']))
                        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            Update available: <strong>{{ $status['pending_version'] }}</strong>
                            @if (!empty($status['pending_notes']))
                                <p class="mt-1 text-xs">{{ $status['pending_notes'] }}</p>
                            @endif
                        </div>
                    @endif

                    @if (session('check_result'))
                        @php $cr = session('check_result'); @endphp
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-700">
                            Last check: current {{ $cr['current_version'] ?? '—' }},
                            latest {{ $cr['latest_version'] ?? '—' }},
                            available {{ !empty($cr['update_available']) ? 'yes' : 'no' }}
                        </div>
                    @endif

                    <div class="mt-6 flex flex-wrap gap-3">
                        @can('settings.update')
                            <form method="POST" action="{{ route('settings.agent.check') }}">
                                @csrf
                                <button type="submit" class="sgr-btn-primary">Check for updates</button>
                            </form>
                            <form method="POST" action="{{ route('settings.agent.apply') }}" onsubmit="return confirm('Apply update now? Agent will restart briefly.');">
                                @csrf
                                <button type="submit" class="rounded-xl border border-cyan-600 bg-white px-4 py-2 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-50">
                                    Make update
                                </button>
                            </form>
                            <form method="POST" action="{{ route('settings.agent.sync-devices') }}">
                                @csrf
                                <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">
                                    Sync devices
                                </button>
                            </form>
                        @endcan
                        <a href="{{ route('settings.agent') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600">Refresh</a>
                    </div>
                </section>
            </div>
        @endunless
    </div>
</x-monitor-layout>
