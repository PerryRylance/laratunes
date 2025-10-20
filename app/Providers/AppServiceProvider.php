<?php

namespace App\Providers;

use App\Services\OlafService;
use App\Support\Olaf;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OlafService::class, fn() => new \App\Services\OlafService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
