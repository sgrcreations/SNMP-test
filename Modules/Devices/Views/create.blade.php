<x-monitor-layout
    title="Add Device"
    header="Add Device"
    subheader="Register a new SNMP endpoint for lab testing"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Devices', 'url' => route('devices.index')],
        ['label' => 'Create'],
    ]"
>
    @include('devices::partials.form', [
        'action' => route('devices.store'),
        'method' => 'POST',
        'vendors' => $vendors,
        'deviceTypes' => $deviceTypes,
        'snmpVersions' => $snmpVersions,
        'authProtocols' => $authProtocols,
        'privProtocols' => $privProtocols,
        'statuses' => $statuses,
    ])
</x-monitor-layout>
