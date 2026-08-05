<?php

namespace Modules\Reports\Providers;

use Illuminate\Support\ServiceProvider;

class ReportsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Phase 3: scheduled and on-demand operational reports.
    }
}
