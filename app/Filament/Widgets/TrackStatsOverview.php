<?php

namespace App\Filament\Widgets;

use App\Models\Track;
use App\Services\DurationService;
use Carbon\CarbonInterval;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrackStatsOverview extends StatsOverviewWidget
{
	protected static ?int $sort = 0;

	protected function getStats(): array
	{
		$duration = CarbonInterval::seconds(DurationService::getTracksTotalDuration())->cascade()->forHumans();

		return [
			Stat::make('Total Tracks', Track::count()),
			Stat::make('Total Duration', $duration),
		];
	}
}
