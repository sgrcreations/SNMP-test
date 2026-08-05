@props([
    'device' => null,
    'vendors',
    'snmpVersions',
    'authProtocols',
    'privProtocols',
    'statuses',
    'action',
    'method' => 'POST',
])

<form method="POST" action="{{ $action }}" class="space-y-6" x-data="{ snmpVersion: '{{ old('snmp_version', $device?->snmp_version?->value ?? 'v2c') }}' }">
    @csrf
    @if(strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Identity</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Name</label>
                    <input name="name" value="{{ old('name', $device?->name) }}" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Vendor</label>
                    <select name="vendor" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                        @foreach($vendors as $value => $label)
                            <option value="{{ $value }}" @selected(old('vendor', $device?->vendor?->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Model</label>
                    <input name="model" value="{{ old('model', $device?->model) }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Hostname</label>
                    <input name="hostname" value="{{ old('hostname', $device?->hostname) }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">IP Address</label>
                    <input name="ip_address" value="{{ old('ip_address', $device?->ip_address) }}" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Port</label>
                    <input type="number" name="port" value="{{ old('port', $device?->port ?? 161) }}" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Location</label>
                    <input name="location" value="{{ old('location', $device?->location) }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Description</label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">{{ old('description', $device?->description) }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">SNMP Credentials</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">SNMP Version</label>
                    <select name="snmp_version" x-model="snmpVersion" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                        @foreach($snmpVersions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Polling Interval (sec)</label>
                    <input type="number" name="polling_interval" value="{{ old('polling_interval', $device?->polling_interval ?? 60) }}" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Status</label>
                    <select name="status" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $device?->status?->value ?? 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2" x-show="snmpVersion === 'v2c'">
                    <label class="mb-1 block text-sm font-medium">Community</label>
                    <input type="password" name="community" value="{{ old('community') }}" placeholder="{{ $device ? 'Leave blank to keep existing' : 'public' }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950" autocomplete="off">
                </div>

                <template x-if="snmpVersion === 'v3'">
                    <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Username</label>
                            <input name="username" value="{{ old('username', $device?->username) }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Auth Protocol</label>
                            <select name="auth_protocol" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                                @foreach($authProtocols as $value => $label)
                                    <option value="{{ $value }}" @selected(old('auth_protocol', $device?->auth_protocol?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Auth Password</label>
                            <input type="password" name="auth_password" value="{{ old('auth_password') }}" placeholder="{{ $device ? 'Leave blank to keep existing' : '' }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950" autocomplete="new-password">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Privacy Protocol</label>
                            <select name="priv_protocol" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                                @foreach($privProtocols as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priv_protocol', $device?->priv_protocol?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Privacy Password</label>
                            <input type="password" name="priv_password" value="{{ old('priv_password') }}" placeholder="{{ $device ? 'Leave blank to keep existing' : '' }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950" autocomplete="new-password">
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ $device ? route('devices.show', $device) : route('devices.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-700">Cancel</a>
        <button class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-500">
            {{ $device ? 'Update Device' : 'Create Device' }}
        </button>
    </div>
</form>
