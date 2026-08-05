@php
    $mapPayload = $mapped->map(fn ($d) => [
        'id' => $d->id,
        'name' => $d->name,
        'ip' => $d->ip_address,
        'type' => $d->device_type?->label() ?? 'Device',
        'typeValue' => $d->device_type?->value ?? 'generic',
        'area' => $d->area ?: ($d->location ?: ''),
        'reach' => $d->reachability?->value ?? 'unknown',
        'cpu' => $d->last_cpu,
        'lat' => (float) $d->latitude,
        'lng' => (float) $d->longitude,
        'url' => route('devices.show', $d),
    ])->values();
@endphp

<x-monitor-layout
    title="Network Map"
    header="Network Map"
    status="Live"
    subheader="Geographic monitoring view · pin only devices with real coordinates"
    :breadcrumbs="[
        ['label' => 'Devices', 'url' => route('devices.index')],
        ['label' => 'Map'],
    ]"
>
    <x-slot:actions>
        <a href="{{ route('devices.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Devices</a>
        @can('devices.create')
            <a href="{{ route('devices.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-cyan-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-cyan-500">Add Device</a>
        @endcan
    </x-slot:actions>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([
            ['Mapped', $mapped->count(), 'text-cyan-600'],
            ['Unmapped', $unmapped->count(), 'text-slate-600'],
            ['Online', $stats['online'], 'text-emerald-600'],
            ['Offline', $stats['offline'], 'text-rose-600'],
            ['Open Alarms', $stats['open_alerts'], 'text-amber-600'],
        ] as [$label, $value, $color])
            <div class="sgr-card p-4">
                <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ $label }}</div>
                <div class="mt-2 text-3xl font-bold {{ $color }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-12">
        <div class="sgr-card overflow-hidden xl:col-span-8">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <div>
                    <h3 class="font-semibold">Live Site Map</h3>
                    <p class="text-xs text-slate-400">Green = online · Red = offline · Gray = unknown</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-700">{{ $mapped->count() }} pins</span>
            </div>
            <div id="deviceMap" class="h-[560px] w-full bg-slate-100"></div>
            @if($mapped->isEmpty())
                <div class="border-t border-slate-100 px-5 py-4 text-sm text-slate-500">
                    No devices have latitude/longitude yet. Set coordinates on a device edit form to place it on the map.
                </div>
            @endif
        </div>

        <div class="space-y-4 xl:col-span-4">
            <div class="sgr-card overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="font-semibold">Unmapped Devices</h3>
                    <p class="text-xs text-slate-400">Edit to add lat/lng for monitoring pins</p>
                </div>
                <div class="max-h-[360px] divide-y divide-slate-100 overflow-y-auto">
                    @forelse($unmapped as $device)
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <a href="{{ route('devices.show', $device) }}" class="block truncate font-semibold text-slate-900 hover:text-cyan-700">{{ $device->name }}</a>
                                <div class="truncate text-xs text-slate-400">{{ $device->ip_address }} · {{ $device->area ?: ($device->location ?: 'No area') }}</div>
                            </div>
                            @can('devices.update')
                                <a href="{{ route('devices.edit', $device) }}" class="shrink-0 rounded-lg border border-slate-200 px-2.5 py-1 text-[11px] font-bold uppercase text-slate-600 hover:bg-slate-50">Edit</a>
                            @endcan
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-400">All devices are mapped.</div>
                    @endforelse
                </div>
            </div>

            <div class="sgr-card overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="font-semibold">Open Alarms</h3>
                </div>
                <div class="max-h-[180px] divide-y divide-slate-100 overflow-y-auto">
                    @forelse($alerts as $alert)
                        <a href="{{ route('devices.show', [$alert->device_id, 'tab' => 'alarms']) }}" class="block px-4 py-3 hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm font-semibold">{{ $alert->title }}</span>
                                <span class="shrink-0 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-700">{{ $alert->severity }}</span>
                            </div>
                            <div class="mt-0.5 truncate text-xs text-slate-400">{{ $alert->device?->name }} · {{ $alert->raised_at?->diffForHumans() }}</div>
                        </a>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-400">No open alarms.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.L) return;

        const devices = @json($mapPayload);
        const map = L.map('deviceMap', { zoomControl: true }).setView([20.5937, 78.9629], 5);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);

        const colorFor = (reach) => {
            if (reach === 'online') return '#059669';
            if (reach === 'offline') return '#e11d48';
            return '#64748b';
        };

        const markers = [];
        devices.forEach((d) => {
            const icon = L.divIcon({
                className: '',
                html: `<div style="width:18px;height:18px;border-radius:9999px;background:${colorFor(d.reach)};border:3px solid #fff;box-shadow:0 1px 4px rgba(15,23,42,.35)"></div>`,
                iconSize: [18, 18],
                iconAnchor: [9, 9],
            });
            const marker = L.marker([d.lat, d.lng], { icon }).addTo(map);
            marker.bindPopup(`
                <div style="min-width:180px;font-family:Plus Jakarta Sans,sans-serif">
                    <div style="font-weight:700;margin-bottom:4px">${d.name}</div>
                    <div style="font-size:12px;color:#64748b;margin-bottom:6px">${d.type} · ${d.ip}</div>
                    <div style="font-size:12px;margin-bottom:8px">Status: <strong style="color:${colorFor(d.reach)}">${(d.reach || 'unknown').toUpperCase()}</strong>${d.cpu != null ? ` · CPU ${d.cpu}%` : ''}</div>
                    <a href="${d.url}" style="display:inline-block;background:#0891b2;color:#fff;text-decoration:none;padding:6px 10px;border-radius:8px;font-size:12px;font-weight:600">Open device</a>
                </div>
            `);
            markers.push(marker);
        });

        if (markers.length === 1) {
            map.setView([devices[0].lat, devices[0].lng], 12);
        } else if (markers.length > 1) {
            map.fitBounds(L.featureGroup(markers).getBounds().pad(0.2));
        }
    });
    </script>
    @endpush
</x-monitor-layout>
