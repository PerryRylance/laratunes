<?php

namespace App\Providers;

use App\Services\FfmpegService;
use App\Services\FifoService;
use App\Services\NowPlayingService;
use App\Services\OlafService;
use App\Services\TransmissionService;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use PerryRylance\Livewire\Providers\DomAssertionProvider;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
		// NB: This fixes 401 on invalid signature when uploading files on a setup using CloudFlare tunnel
		UrlGenerator::macro(
			'alternateHasCorrectSignature',
			function (Request $request, $absolute = true, array $ignoreQuery = []) {
				$ignoreQuery[] = 'signature';

				$absoluteUrl = url($request->path());
				$url = $absolute ? $absoluteUrl : '/'.$request->path();

				$queryString = collect(explode('&', (string) $request
					->server->get('QUERY_STRING')))
					->reject(fn ($parameter) => in_array(Str::before($parameter, '='), $ignoreQuery))
					->join('&');

				$original = rtrim($url.'?'.$queryString, '?');

				$signature = hash_hmac('sha256', $original, call_user_func($this->keyResolver)[0]);

				return hash_equals($signature, (string) $request->query('signature', ''));
			}
		);

		UrlGenerator::macro('alternateHasValidSignature', function (Request $request, $absolute = true, array $ignoreQuery = []) {
			return URL::alternateHasCorrectSignature($request, $absolute, $ignoreQuery)
				&& URL::signatureHasNotExpired($request);
		});

		Request::macro('hasValidSignature', function ($absolute = true, array $ignoreQuery = []) {
			return URL::alternateHasValidSignature($this, $absolute, $ignoreQuery);
		});

		$this->app->singleton(OlafService::class, fn () => new OlafService);
		$this->app->singleton(NowPlayingService::class, fn () => new NowPlayingService);
		$this->app->singleton(FfmpegService::class, fn () => new FfmpegService);
		$this->app->singleton(FifoService::class, fn () => new FifoService);
		$this->app->singleton(TransmissionService::class, fn () => new TransmissionService);

		FilamentAsset::register([
			Js::make('chart-js-plugins', Vite::asset('resources/js/filament-chart-js-plugins.js'))->module(),
		]);
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void
	{
		if (App::runningUnitTests())(new DomAssertionProvider(app()))->boot();

		// NB: Stops the scripts being served up via HTTP
		if ($this->app->environment('production'))
			URL::forceScheme('https');
	}
}
