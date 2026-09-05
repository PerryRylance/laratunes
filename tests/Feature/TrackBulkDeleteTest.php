<?php

namespace Tests\Feature;

use App\Facades\Ffmpeg;
use App\Filament\Resources\Tracks\Pages\ListTracks;
use App\Models\Track;
use App\Services\DurationService;
use Filament\Actions\DeleteBulkAction;
use Livewire\Livewire;
use Tests\AdminTestCase;

class TrackBulkDeleteTest extends AdminTestCase
{
	public function testBulkDeletingTracksInvalidatesTheCachedTotalDuration(): void
	{
		Ffmpeg::shouldReceive('getDuration')->twice()->andReturn(180);

		$tracks = Track::factory()->uploaded()->count(2)->create();

		$this->assertEquals(360, DurationService::getTracksTotalDuration());

		Livewire::test(ListTracks::class)
			->callTableBulkAction(DeleteBulkAction::class, $tracks);

		$this->assertEquals(0, DurationService::getTracksTotalDuration());
	}
}
