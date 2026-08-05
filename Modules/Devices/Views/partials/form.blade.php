@props([
    'device' => null,
    'vendors',
    'deviceTypes' => [],
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
        <section class="sgr-card p-5">
            <h2 class="mb-4 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Identity</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Name</label>
                    <input name="name" value="{{ old('name', $device?->name) }}" required class="sgr-input">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Vendor</label>
                    <select name="vendor" required class="sgr-input">
                        @foreach($vendors as $value => $label)
                            <option value="{{ $value }}" @selected(old('vendor', $device?->vendor?->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Device Type</label>
                    <select name="device_type" class="sgr-input">
                        @foreach($deviceTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('device_type', $device?->device_type?->value ?? 'generic') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-400">
                        Set to <strong>OLT</strong> for MA5800/GPON devices to unlock Command Center, PON Ports, ONUs and Network Tree.
                    </p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Model</label>
                    <input name="model" value="{{ old('model', $device?->model) }}" class="sgr-input">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Hostname</label>
                    <input name="hostname" value="{{ old('hostname', $device?->hostname) }}" class="sgr-input">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">IP Address</label>
                    <input name="ip_address" value="{{ old('ip_address', $device?->ip_address) }}" required class="sgr-input">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Port</label>
                    <input type="number" name="port" value="{{ old('port', $device?->port ?? 161) }}" required class="sgr-input">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Location</label>
                    <input name="location" value="{{ old('location', $device?->location) }}" class="sgr-input">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Area</label>
                    <input name="area" value="{{ old('area', $device?->area) }}" class="sgr-input" placeholder="e.g. AMBUR, VELLORE">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Latitude</label>
                    <input type="number" step="any" name="latitude" value="{{ old('latitude', $device?->latitude) }}" class="sgr-input" placeholder="12.9165">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Longitude</label>
                    <input type="number" step="any" name="longitude" value="{{ old('longitude', $device?->longitude) }}" class="sgr-input" placeholder="79.1325">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Description</label>
                    <textarea name="description" rows="3" class="sgr-input">{{ old('description', $device?->description) }}</textarea>
                    <p class="mt-1.5 text-xs text-slate-400">Latitude and longitude place this device on the Network Map (real coords only).</p>
                </div>
            </div>
        </section>

        <section class="sgr-card p-5">
            <h2 class="mb-4 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">SNMP Credentials</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">SNMP Version</label>
                    <select name="snmp_version" x-model="snmpVersion" required class="sgr-input">
                        @foreach($snmpVersions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Polling Interval (sec)</label>
                    <input type="number" name="polling_interval" value="{{ old('polling_interval', $device?->polling_interval ?? 60) }}" required class="sgr-input">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Status</label>
                    <select name="status" required class="sgr-input">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $device?->status?->value ?? 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2" x-show="snmpVersion === 'v2c'">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Community</label>
                    <input type="password" name="community" value="{{ old('community') }}" placeholder="{{ $device ? 'Leave blank to keep existing' : 'public' }}" class="sgr-input" autocomplete="off">
                </div>

                <template x-if="snmpVersion === 'v3'">
                    <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Username</label>
                            <input name="username" value="{{ old('username', $device?->username) }}" class="sgr-input">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Auth Protocol</label>
                            <select name="auth_protocol" class="sgr-input">
                                @foreach($authProtocols as $value => $label)
                                    <option value="{{ $value }}" @selected(old('auth_protocol', $device?->auth_protocol?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Auth Password</label>
                            <input type="password" name="auth_password" value="{{ old('auth_password') }}" placeholder="{{ $device ? 'Leave blank to keep existing' : '' }}" class="sgr-input" autocomplete="new-password">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Privacy Protocol</label>
                            <select name="priv_protocol" class="sgr-input">
                                @foreach($privProtocols as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priv_protocol', $device?->priv_protocol?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Privacy Password</label>
                            <input type="password" name="priv_password" value="{{ old('priv_password') }}" placeholder="{{ $device ? 'Leave blank to keep existing' : '' }}" class="sgr-input" autocomplete="new-password">
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ $device ? route('devices.show', $device) : route('devices.index') }}" class="sgr-btn-secondary">Cancel</a>
        <button class="sgr-btn-primary">
            {{ $device ? 'Update Device' : 'Create Device' }}
        </button>
    </div>
</form>
