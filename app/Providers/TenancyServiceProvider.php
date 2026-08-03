<?php

namespace App\Providers;

use App\Tenancy\TenantContext;
use App\Tenancy\TenantManager;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(TenantManager::class, function ($app) {
            return new TenantManager($app->make(TenantContext::class));
        });
    }

    public function boot(): void
    {
        //
    }
}
