<?php

namespace App\Filament\Widgets;

use App\Models\Track;
use Filament\Widgets\ChartWidget;

class TrackAvailabilityChart extends ChartWidget
{
	protected ?string $heading = 'Playlist Progress';

	protected static ?int $sort = 1;

	protected ?string $maxHeight = '256px';

	protected function getData(): array
	{
		$played = Track::where('available', '=', 0)->count();
		$remaining = Track::where('available', '>', 0)->count();

		return [
			'datasets' => [
				[
					'data' => [$played, $remaining],
					'backgroundColor' => ['oklch(0.666 0.179 58.318)', '#c3c2b7'],
					'borderWidth' => 0,
				],
			],
			'labels' => ['Played', 'Remaining'],
		];
	}

	protected function getType(): string
	{
		return 'doughnut';
	}

	protected function getOptions(): array
	{
		return [
			'cutout' => '75%',
			'plugins' => [
				'legend' => [
					'display' => true,
					'position' => 'bottom',
				],
			],
		];
	}
}
