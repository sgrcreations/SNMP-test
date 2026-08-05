<x-monitor-layout
    title="Settings"
    header="Platform Settings"
    subheader="Polling defaults and alert thresholds"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Settings'],
    ]"
>
    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        @foreach($groups as $group => $settings)
            <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', $group) }}</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($settings as $setting)
                        <div>
                            <label class="mb-1 block text-sm font-medium" for="setting-{{ $setting->key }}">
                                {{ $setting->label ?? $setting->key }}
                            </label>
                            @if($setting->description)
                                <p class="mb-2 text-xs text-slate-500">{{ $setting->description }}</p>
                            @endif

                            @if($setting->type === 'boolean')
                                <select id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950">
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
                                    class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950"
                                >
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex justify-end">
            <button class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-500">Save Settings</button>
        </div>
    </form>
</x-monitor-layout>
