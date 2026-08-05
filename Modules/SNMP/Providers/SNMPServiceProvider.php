<?php

namespace Modules\SNMP\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\SNMP\Services\SNMPService;

class SNMPServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SNMPService::class);
    }

    public function boot(): void
    {
        // Phase 2: load OID explorer routes/views here.
    }
}
