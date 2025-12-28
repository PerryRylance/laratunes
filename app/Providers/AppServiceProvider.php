<?php

namespace App\Providers;

use App\Services\FfmpegService;
use App\Services\NowPlayingService;
use App\Services\OlafService;
use App\Services\FifoService;
use App\Services\TransmissionService;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use PerryRylance\Livewire\Providers\DomAssertionProvider;

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

        FilamentAsset::register([
            Js::make('chart-js-plugins', Vite::asset('resources/js/filament-chart-js-plugins.js'))->module(),
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if(App::runningUnitTests())
            (new DomAssertionProvider(app()))->boot();

        // NB: Stops the scripts being served up via HTTP
        if($this->app->environment('production'))
            URL::forceScheme('https');
    }
}
