<?php

namespace Modules\Interfaces\Providers;

use Illuminate\Support\ServiceProvider;

class InterfacesServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Phase 2: interface inventory, utilization views, and sync from SNMP.
    }
}
