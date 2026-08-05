<?php

namespace Modules\Devices\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Devices\Models\Device;
use Modules\Devices\Policies\DevicePolicy;

class DevicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Views', 'devices');

        Gate::policy(Device::class, DevicePolicy::class);

        Route::middleware('web')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
