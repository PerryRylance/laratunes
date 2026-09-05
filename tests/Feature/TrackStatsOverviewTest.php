<?php

namespace Tests\Feature;

use App\Facades\Ffmpeg;
use App\Filament\Widgets\TrackStatsOverview;
use App\Models\Track;
use Livewire\Livewire;
use Tests\AdminTestCase;

class TrackStatsOverviewTest extends AdminTestCase
{
	public function testDisplaysTotalTracksAndTotalDuration(): void
	{
		Ffmpeg::shouldReceive('getDuration')->times(3)->andReturn(200);

		Track::factory()->uploaded()->count(3)->create();

		Livewire::test(TrackStatsOverview::class)
			->assertSee('Total Tracks')
			->assertSee('3')
			->assertSee('Total Duration')
			->assertSee('10 minutes');
	}
}
