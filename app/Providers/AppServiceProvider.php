<?php

namespace App\Providers;

use App\Services\FfmpegService;
use App\Services\NowPlayingService;
use App\Services\OlafService;
use App\Services\FifoService;
use App\Services\TransmissionService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OlafService::class, fn() => new OlafService);
        $this->app->singleton(NowPlayingService::class, fn() => new NowPlayingService);
        $this->app->singleton(FfmpegService::class, fn() => new FfmpegService);
        $this->app->singleton(FifoService::class, fn() => new FifoService);
        $this->app->singleton(TransmissionService::class, fn() => new TransmissionService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
