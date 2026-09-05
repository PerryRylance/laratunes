<?php

namespace Tests\Feature;

use App\Facades\Ffmpeg;
use App\Models\Track;
use Tests\TestCase;

class CalculateDurationsTest extends TestCase
{
	public function testCalculatesDurationForTracksWithZeroDuration(): void
	{
		Ffmpeg::shouldReceive('getDuration')->once()->andReturn(180);

		$track = Track::factory()->uploaded()->create();
		$track->update(['duration' => 0]);

		Ffmpeg::shouldReceive('getDuration')->once()->andReturn(210);

		$this->artisan('app:calculate-durations');

		$this->assertEquals(210, $track->fresh()->duration);
	}

	public function testDoesNotRecalculateTracksWithNonZeroDuration(): void
	{
		Ffmpeg::shouldReceive('getDuration')->once()->andReturn(180);

		$track = Track::factory()->uploaded()->create();

		$this->artisan('app:calculate-durations');

		$this->assertEquals(180, $track->fresh()->duration);
	}
}
