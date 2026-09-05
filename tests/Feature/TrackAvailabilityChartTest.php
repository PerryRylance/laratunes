<?php

namespace Tests\Feature;

use App\Filament\Widgets\TrackAvailabilityChart;
use App\Models\Track;
use Livewire\Livewire;
use Tests\AdminTestCase;

class TrackAvailabilityChartTest extends AdminTestCase
{
	public function testShowsPlayedAndRemainingLabels(): void
	{
		Livewire::test(TrackAvailabilityChart::class)
			->assertSee('Played')
			->assertSee('Remaining');
	}

	public function testCountsPlayedAndRemainingTracksSeparately(): void
	{
		Track::factory()->uploaded()->create(['available' => 0]);
		Track::factory()->uploaded()->count(2)->create(['available' => 1]);

		Livewire::test(TrackAvailabilityChart::class)
			->assertSee('\u0022data\u0022:[1,2]', false);
	}
}
