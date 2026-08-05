<?php

namespace Modules\Settings\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Views', 'settings');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Settings\Console\PublishAgentReleaseCommand::class,
            ]);
        }

        Route::middleware('web')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
