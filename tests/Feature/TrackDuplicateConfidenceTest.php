<?php

namespace Tests\Feature;

use App\Models\Track;
use App\Models\TrackHasDuplicates;
use Tests\TestCase;

class TrackDuplicateConfidenceTest extends TestCase
{
	public function testDuplicatePivotStoresConfidenceFromOlaf(): void
	{
		// NB: Tests\Mocks\Olaf::query() always reports a match against 'fake.mp3' with a
		// confidence of 123, regardless of input - so a track at that path is what makes
		// this scenario resolve to a real duplicate with a known confidence.
		$original = Track::factory()->uploaded(dst: 'fake.mp3')->create();

		$duplicate = Track::factory()->uploaded()->create();

		$this->assertDatabaseHas(TrackHasDuplicates::class, [
			'original_id' => $original->id,
			'duplicate_id' => $duplicate->id,
			'confidence' => 123,
		]);
	}
}
