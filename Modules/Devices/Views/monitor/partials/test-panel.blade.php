<div
    class="mb-4"
    x-data="{
        open: false,
        loading: false,
        result: null,
        async runTest() {
            this.open = true; this.loading = true; this.result = null;
            try {
                const response = await fetch('{{ route('devices.test-snmp', $device) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                });
                this.result = await response.json();
            } catch (e) {
                this.result = { connected: false, message: 'Request failed' };
            } finally { this.loading = false; }
        }
    }"
    @open-snmp-test.window="runTest()"
>
    <div x-show="open" x-cloak class="sgr-card p-5">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold uppercase tracking-[0.12em] text-slate-400">SNMP Test</h3>
            <button class="text-xs font-semibold text-slate-400" @click="open=false">Close</button>
        </div>
        <div x-show="loading" class="mt-3 text-sm text-slate-500">Testing {{ $device->displayEndpoint() }}...</div>
        <template x-if="!loading && result">
            <div class="mt-3 space-y-3">
                <div class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase" :class="result.connected ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" x-text="result.connected ? 'Connected' : 'Connection Failed'"></div>
                <p class="text-sm text-slate-600" x-text="result.message"></p>
                <template x-if="result.connected && result.system">
                    <dl class="grid gap-3 sm:grid-cols-3 text-sm">
                        <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-400">Hostname</dt><dd class="font-semibold" x-text="result.system.hostname || '—'"></dd></div>
                        <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-400">Uptime</dt><dd class="font-semibold" x-text="result.system.uptime || '—'"></dd></div>
                        <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-400">Interfaces</dt><dd class="font-semibold" x-text="result.interfaces_count"></dd></div>
                        <div class="rounded-xl bg-slate-50 p-3 sm:col-span-3"><dt class="text-xs text-slate-400">Description</dt><dd class="font-semibold break-all" x-text="result.system.description || '—'"></dd></div>
                    </dl>
                </template>
            </div>
        </template>
    </div>
</div>
