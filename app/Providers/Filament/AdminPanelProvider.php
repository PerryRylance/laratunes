<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\BufferUsageChart;
use App\Filament\Widgets\ConfigureStreamCallout;
use App\Filament\Widgets\CpuUsageChart;
use App\Filament\Widgets\DiscoverMediaCallout;
use App\Filament\Widgets\MemoryUsageChart;
use App\Filament\Widgets\StatusWidget;
use App\Filament\Widgets\TrackAvailabilityChart;
use App\Filament\Widgets\TrackStatsOverview;
use App\Filament\Widgets\TransmissionBitrateChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
	public function panel(Panel $panel): Panel
	{
		$charts = [
			TrackAvailabilityChart::class,
			CpuUsageChart::class,
			TransmissionBitrateChart::class,
			BufferUsageChart::class,
			MemoryUsageChart::class,
		];

		$widgets = [
			TrackStatsOverview::class,
			ConfigureStreamCallout::class,
			DiscoverMediaCallout::class,
		];

		// TODO: Conditions - can't start stream with no tracks and missing settings
		$widgets[] = StatusWidget::class;

		return $panel
			->default()
			->id('admin')
			->path('admin')
			->login()
			->colors([
				'primary' => Color::Amber,
			])
			->viteTheme('resources/css/filament/admin/theme.css')
			->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
			->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
			->pages([])
			->widgets([
				...$widgets,
				...$charts,
			])
			->brandLogo(fn () => view('filament.logo'))
			->middleware([
				EncryptCookies::class,
				AddQueuedCookiesToResponse::class,
				StartSession::class,
				AuthenticateSession::class,
				ShareErrorsFromSession::class,
				VerifyCsrfToken::class,
				SubstituteBindings::class,
				DisableBladeIconComponents::class,
				DispatchServingFilamentEvent::class,
			])
			->authMiddleware([
				Authenticate::class,
			]);
	}
}
