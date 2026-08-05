@php
    $reach = $device->reachability?->value;
    $statusLabel = match ($reach) {
        'online' => 'Online',
        'offline' => 'Offline',
        default => 'Unknown',
    };
    $isOltWorkspace = $device->hasOltWorkspace();
    $tabs = $isOltWorkspace
        ? [
            'command-center' => 'Command Center',
            'pon' => 'PON Ports',
            'ports' => 'Uplink Ports',
            'onus' => 'ONUs',
            'tree' => 'Network Tree',
            'vlans' => 'VLAN Details',
            'logs' => 'Status Logs',
            'alarms' => 'Alarms',
            'polling' => 'Polling Health',
        ]
        : [
            'overview' => 'Overview',
            'ports' => 'Switch Ports',
            'vlans' => 'VLAN Details',
            'logs' => 'Status Logs',
            'alarms' => 'Alarms',
            'polling' => 'Polling Health',
        ];
    if (! array_key_exists($tab, $tabs)) {
        $tab = array_key_first($tabs);
    }
@endphp

<x-monitor-layout
    title="{{ $device->name }}"
    header="{{ $device->name }}"
    :status="$statusLabel"
    :meta="($device->manufacturer ?: $device->vendor?->label()).' '.($device->model ?: '').' · '.$device->ip_address.' · Polls '.($pollingProfile['interval_label'] ?? ('every '.$device->polling_interval.'s')).($device->sys_uptime ? ' · Uptime '.$device->sys_uptime : '')"
    :breadcrumbs="[
        ['label' => 'Devices', 'url' => route('devices.index')],
        ['label' => $device->name],
    ]"
>
    <x-slot:actions>
        @if($isOltWorkspace)
            <div class="mr-1 hidden items-center gap-2 lg:flex">
                <div class="rounded-xl border border-amber-100 bg-amber-50 px-3 py-1.5 text-center">
                    <div class="text-[10px] font-bold uppercase text-amber-600">Health</div>
                    <div class="text-sm font-bold text-amber-700">{{ max(0, 100 - (int) ($overview['open_alerts'] * 8) - (int) ($overview['packet_loss_pct'] ?? 0)) }}</div>
                </div>
                <div class="rounded-xl border border-sky-100 bg-sky-50 px-3 py-1.5 text-center">
                    <div class="text-[10px] font-bold uppercase text-sky-600">CPU</div>
                    <div class="text-sm font-bold text-sky-700">{{ $overview['cpu'] !== null ? $overview['cpu'].'%' : '—' }}</div>
                </div>
                <div class="rounded-xl border border-fuchsia-100 bg-fuchsia-50 px-3 py-1.5 text-center">
                    <div class="text-[10px] font-bold uppercase text-fuchsia-600">Mem</div>
                    <div class="text-sm font-bold text-fuchsia-700">{{ $overview['memory'] !== null ? $overview['memory'].'%' : '—' }}</div>
                </div>
                <div class="rounded-xl border border-orange-100 bg-orange-50 px-3 py-1.5 text-center">
                    <div class="text-[10px] font-bold uppercase text-orange-600">Temp</div>
                    <div class="text-sm font-bold text-orange-700">{{ $overview['temperature'] !== null ? $overview['temperature'].'°C' : '—' }}</div>
                </div>
            </div>
        @endif
        <form method="POST" action="{{ route('devices.poll', $device) }}">
            @csrf
            <input type="hidden" name="sync" value="1">
            <button class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-50" title="Sync now">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
            </button>
        </form>
        <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-cyan-200 bg-cyan-50 px-4 text-sm font-semibold text-cyan-700" x-data @click="$dispatch('open-snmp-test')">Test SNMP</button>
        @can('devices.update')
            <a href="{{ route('devices.edit', $device) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-50" title="Edit">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.1 2.1 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </a>
        @endcan
    </x-slot:actions>

    @include('devices::monitor.partials.test-panel')

    <div class="mb-4 flex flex-wrap gap-2 overflow-x-auto rounded-2xl border border-slate-100 bg-white p-2">
        @foreach($tabs as $key => $label)
            <a href="{{ route('devices.show', ['device' => $device, 'tab' => $key, 'range' => $range]) }}"
               @class([
                   'rounded-xl px-3 py-2 text-xs font-bold uppercase tracking-wide whitespace-nowrap',
                   'bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200' => $tab === $key,
                   'text-slate-500 hover:bg-slate-50' => $tab !== $key,
               ])>{{ $label }}</a>
        @endforeach
    </div>

    @include('devices::monitor.tabs.'.str_replace('-', '_', $tab))
</x-monitor-layout>
