<?php

namespace Modules\Metrics\Providers;

use Illuminate\Support\ServiceProvider;

class MetricsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Phase 2: device_metrics / interface_metrics storage and chart APIs.
    }
}
