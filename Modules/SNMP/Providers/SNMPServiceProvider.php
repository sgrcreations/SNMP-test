<?php

namespace Modules\SNMP\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Alerts\Services\AlertEvaluationService;
use Modules\SNMP\Console\PollDevicesCommand;
use Modules\SNMP\Services\DevicePollService;
use Modules\SNMP\Services\HuaweiOltCollector;
use Modules\SNMP\Services\SnmpClientFactory;
use Modules\SNMP\Services\SNMPService;

class SNMPServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SnmpClientFactory::class);
        $this->app->singleton(SNMPService::class);
        $this->app->singleton(HuaweiOltCollector::class);
        $this->app->singleton(AlertEvaluationService::class);
        $this->app->singleton(DevicePollService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PollDevicesCommand::class,
            ]);
        }
    }
}
