<x-monitor-layout
    title="Edit Device"
    header="Edit {{ $device->name }}"
    subheader="Update SNMP connection settings"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Devices', 'url' => route('devices.index')],
        ['label' => $device->name, 'url' => route('devices.show', $device)],
        ['label' => 'Edit'],
    ]"
>
    @include('devices::partials.form', [
        'device' => $device,
        'action' => route('devices.update', $device),
        'method' => 'PUT',
        'vendors' => $vendors,
        'deviceTypes' => $deviceTypes,
        'snmpVersions' => $snmpVersions,
        'authProtocols' => $authProtocols,
        'privProtocols' => $privProtocols,
        'statuses' => $statuses,
    ])
</x-monitor-layout>
