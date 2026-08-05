<?php

namespace Modules\Metrics\Providers;

use Illuminate\Support\ServiceProvider;

class MetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
