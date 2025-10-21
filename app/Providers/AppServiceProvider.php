<?php

namespace App\Providers;

use App\Services\NowPlayingService;
use App\Services\OlafService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OlafService::class, fn() => new \App\Services\OlafService);
        $this->app->singleton(NowPlayingService::class, fn() => new \App\Services\NowPlayingService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
