<?php

namespace Modules\Alerts\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AlertsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Views', 'alerts');

        Route::middleware('web')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
