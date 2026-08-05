<x-monitor-layout
    title="Agent Updates"
    header="Agent Updates"
    subheader="Check and apply updates for the on-prem Go snmp-agent"
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

    @unless ($configured)
        <section class="sgr-card p-5">
            <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-400">Not configured</h2>
            <p class="mt-2 text-sm text-slate-600">
                Set <strong>SNMP Agent URL</strong> and <strong>SNMP Agent API Key</strong> under
                <a href="{{ route('settings.edit') }}" class="font-semibold text-cyan-700 hover:underline">Settings → agent</a>,
                then return here.
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
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Updates enabled</dt>
                        <dd class="font-semibold text-slate-900">{{ !empty($status['enabled']) ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Auto-apply</dt>
                        <dd class="font-semibold text-slate-900">{{ !empty($status['auto_apply']) ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Channel URL</dt>
                        <dd class="mt-1 break-all font-mono text-xs text-slate-700">{{ $status['channel_url'] ?: 'Not set on agent' }}</dd>
                    </div>
                    @if (!empty($status['last_error']))
                        <div>
                            <dt class="text-slate-500">Last error</dt>
                            <dd class="mt-1 text-rose-700">{{ $status['last_error'] }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="sgr-card p-5">
                <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-400">Update actions</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Downloads happen in the background on the agent host. Downtime is only the stop/start phase (typically 2–5 seconds). Failed startups roll back automatically.
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
                    @php
                        $cr = session('check_result');
                    @endphp
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
                        <form method="POST" action="{{ route('settings.agent.apply') }}" onsubmit="return confirm('Apply the update now? The agent will restart for a few seconds.');">
                            @csrf
                            <button type="submit" class="rounded-xl border border-cyan-600 bg-white px-4 py-2 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-50">
                                Apply update
                            </button>
                        </form>
                        <form method="POST" action="{{ route('settings.agent.sync-devices') }}" onsubmit="return confirm('Push all Laravel devices (with SNMP secrets) to the agent now?');">
                            @csrf
                            <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Sync devices now
                            </button>
                        </form>
                    @endcan
                    <a href="{{ route('settings.agent') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300">
                        Refresh status
                    </a>
                </div>
                <p class="mt-4 text-xs text-slate-500">
                    Device create/update/delete in Laravel also syncs automatically when the agent URL and API key are set.
                </p>
            </section>
        </div>
    @endunless
</x-monitor-layout>
