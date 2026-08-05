<?php

namespace Modules\Alerts\Providers;

use Illuminate\Support\ServiceProvider;

class AlertsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Phase 2: alert evaluation engine and acknowledgement workflows.
    }
}
