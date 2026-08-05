<?php

namespace Modules\Interfaces\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InterfacesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Views', 'interfaces');

        if (file_exists(__DIR__.'/../Routes/web.php')) {
            Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
        }
    }
}
