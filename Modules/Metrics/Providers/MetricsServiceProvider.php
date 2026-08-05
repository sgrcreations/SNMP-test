<?php

namespace Modules\Metrics\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Metrics\Console\RollupMetricsCommand;

class MetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RollupMetricsCommand::class,
            ]);
        }
    }
}
