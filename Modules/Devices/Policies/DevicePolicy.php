<?php

namespace Modules\Devices\Policies;

use App\Models\User;
use Modules\Devices\Models\Device;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('devices.view');
    }

    public function view(User $user, Device $device): bool
    {
        return $user->can('devices.view');
    }

    public function create(User $user): bool
    {
        return $user->can('devices.create');
    }

    public function update(User $user, Device $device): bool
    {
        return $user->can('devices.update');
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->can('devices.delete');
    }
}
