<x-monitor-layout
    title="Settings"
    header="Settings"
    subheader="Platform defaults and alert thresholds"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Settings'],
    ]"
>
    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        @foreach($groups as $group => $settings)
            <section class="sgr-card p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-400">{{ str_replace('_', ' ', $group) }}</h2>
                        <p class="mt-1 text-xs text-slate-400">Configure {{ strtolower(str_replace('_', ' ', $group)) }} options</p>
                    </div>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($settings as $setting)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <label class="mb-1 block text-sm font-semibold text-slate-800" for="setting-{{ $setting->key }}">
                                {{ $setting->label ?? $setting->key }}
                            </label>
                            @if($setting->description)
                                <p class="mb-3 text-xs text-slate-500">{{ $setting->description }}</p>
                            @endif

                            @if($setting->type === 'boolean')
                                <select id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]" class="sgr-input">
                                    <option value="1" @selected((string) $setting->typedValue() === '1' || $setting->typedValue() === true)>Enabled</option>
                                    <option value="0" @selected((string) $setting->typedValue() === '0' || $setting->typedValue() === false)>Disabled</option>
                                </select>
                            @else
                                <input
                                    id="setting-{{ $setting->key }}"
                                    name="settings[{{ $setting->key }}]"
                                    type="{{ $setting->type === 'integer' ? 'number' : 'text' }}"
                                    value="{{ old('settings.'.$setting->key, $setting->is_encrypted ? '' : $setting->typedValue()) }}"
                                    placeholder="{{ $setting->is_encrypted ? 'Encrypted value set' : '' }}"
                                    class="sgr-input"
                                >
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex justify-end">
            <button class="sgr-btn-primary">Save Settings</button>
        </div>
    </form>
</x-monitor-layout>
