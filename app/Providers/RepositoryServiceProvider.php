<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Devices\Repositories\DeviceRepository;
use Modules\Devices\Repositories\Contracts\DeviceRepositoryInterface;
use Modules\Settings\Repositories\SettingRepository;
use Modules\Settings\Repositories\Contracts\SettingRepositoryInterface;
use Modules\Authentication\Repositories\AuditLogRepository;
use Modules\Authentication\Repositories\Contracts\AuditLogRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DeviceRepositoryInterface::class, DeviceRepository::class);
        $this->app->bind(SettingRepositoryInterface::class, SettingRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogRepository::class);
    }
}
