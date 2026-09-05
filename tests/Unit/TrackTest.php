<?php

namespace Tests\Unit;

use App\Facades\Ffmpeg;
use App\Models\Track;
use App\Models\TrackHasDuplicates;
use App\Services\DurationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Tests\TestCase;
use Tests\TestFiles;

class TrackTest extends TestCase
{
	public function testCaptionIsArtistThenTitle(): void
	{
		$track = Track::factory()->uploaded()->create();

		$this->assertEquals("{$track->artist} - {$track->title}", $track->caption);
	}

	public function testUnknownArtistAndTitleCaption(): void
	{
		$track = Track::factory()->uploaded()->create([
			'artist' => null,
			'title' => null,
		]);

		$this->assertEquals('Unknown Artist - Unknown Title', $track->caption);
	}

	public function testPathHasUniqueConstraint(): void
	{
		$file = TestFiles::all()->first();

		Track::factory()->uploaded(dst: $file)->create();

		$this->expectException(UniqueConstraintViolationException::class);

		Track::factory()->uploaded(dst: $file)->create();
	}

	public function testHashHasUniqueConstraint(): void
	{
		$file = TestFiles::all()->first();

		Track::factory()->uploaded(src: $file)->create();

		$this->expectException(UniqueConstraintViolationException::class);

		Track::factory()->uploaded(src: $file)->create();
	}

	public function testAdminLink(): void {}

	public function testDeletingOriginalTrackCascadesToTrackHasDuplicates(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		$this->assertDatabaseHas(TrackHasDuplicates::class, [
			'original_id' => $original->id,
			'duplicate_id' => $duplicate->id,
		]);

		$original->delete();

		$this->assertDatabaseMissing(TrackHasDuplicates::class, [
			'original_id' => $original->id,
			'duplicate_id' => $duplicate->id,
		]);
	}

	public function testDeletingDuplicateTrackCascadesToTrackHasDuplicates(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		$duplicate->delete();

		$this->assertDatabaseMissing(TrackHasDuplicates::class, [
			'original_id' => $original->id,
			'duplicate_id' => $duplicate->id,
		]);
	}

	public function testBulkDeletingTracksCascadesToTrackHasDuplicates(): void
	{
		// NB: A bulk/query delete (eg. Track::query()->delete()) bypasses Eloquent model events
		// entirely, so TrackObserver can't clean this up - only a real database-level cascade can.
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		Track::query()->delete();

		$this->assertDatabaseMissing(TrackHasDuplicates::class, [
			'original_id' => $original->id,
			'duplicate_id' => $duplicate->id,
		]);
	}

	public function testUrl(): void
	{
		$hash = md5('test');
		$track = Track::factory()->create([
			'hash' => $hash,
		]);
		$expected = url("/tracks/$hash");

		$this->assertEquals($expected, $track->url);
	}

	public function testCreatingATrackStoresItsDuration(): void
	{
		Ffmpeg::shouldReceive('getDuration')->once()->andReturn(180);

		$track = Track::factory()->uploaded()->create();

		$this->assertEquals(180, $track->fresh()->duration);
	}

	public function testCreatingATrackUpdatesTheCachedTotalDuration(): void
	{
		Ffmpeg::shouldReceive('getDuration')->once()->andReturn(180);

		Track::factory()->uploaded()->create();

		$this->assertEquals(180, DurationService::getTracksTotalDuration());
	}

	public function testDeletingATrackInvalidatesTheCachedTotalDuration(): void
	{
		Ffmpeg::shouldReceive('getDuration')->once()->andReturn(180);

		$track = Track::factory()->uploaded()->create();

		$this->assertEquals(180, DurationService::getTracksTotalDuration());

		$track->delete();

		$this->assertEquals(0, DurationService::getTracksTotalDuration());
	}
}
