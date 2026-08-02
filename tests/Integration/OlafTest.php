<?php

namespace Tests\Integration;

use App\Facades\Olaf;
use App\Filament\Resources\Tracks\Pages\CreateTrack;
use App\Models\Track;
use App\Models\TrackHasDuplicates;
use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kiwilan\Audio\Audio;
use Livewire\Livewire;
use Tests\Attributes\UsesRealOlaf;
use Tests\TestCase;
use Tests\TestFiles;

#[UsesRealOlaf]
class OlafTest extends TestCase
{
	protected function afterRefreshingDatabase()
	{
		Olaf::reset();
	}

	public function testReset(): void
	{
		$file = TestFiles::all()->first();

		TestFiles::upload($file);

		Olaf::fingerprint($file);
		Olaf::reset();

		$this->assertFileDoesNotExist('./.olaf/docker_dbs/db/data.mdb');
		$this->assertFileDoesNotExist('./.olaf/docker_dbs/db/lock.mdb');
	}

	public function testStats(): void
	{
		// NB: Store one file first otherwise it'll error because the Olaf DB doesn't exist
		$file = TestFiles::all()->first();

		TestFiles::upload($file);
		Olaf::fingerprint($file);

		$stats = Olaf::stats();

		$this->assertEquals(0, $stats->databaseFileSizeInMb);
		$this->assertEquals(1, $stats->numberOfSongs);
	}

	public function testStore(): void
	{
		$this->assertEquals(0, Olaf::stats()->numberOfSongs);

		$file = TestFiles::all()->first();

		TestFiles::upload($file);
		Olaf::fingerprint($file);

		$this->assertEquals(1, Olaf::stats()->numberOfSongs);
	}

	public function testQuery(): void
	{
		$files = TestFiles::all();
		$expected = $files->take(1)->first();
		$others = $files->slice(1);

		TestFiles::upload($expected);
		Olaf::fingerprint($expected);

		foreach ($others as $other)
		{
			TestFiles::upload($other);
			Olaf::fingerprint($other);
		}

		Storage::disk('media')->put('query.mp3', file_get_contents("./tests/Fixtures/media/$expected"));

		$results = Olaf::query('query.mp3');

		$best = $results->items->first();

		$this->assertEquals($expected, $best['file']);
		$this->assertEquals(641, $best['confidence']);
	}

	public function testQueryDoesNotYieldFalsePositives(): void
	{
		$files = TestFiles::all();
		$unexpected = $files->take(1)->first();
		$others = $files->slice(1);

		TestFiles::upload($unexpected);

		foreach ($others as $other)
		{
			TestFiles::upload($other);
			Olaf::fingerprint($other);
		}

		$results = Olaf::query($unexpected);

		$this->assertCount(0, $results->items);
	}

	public function testDelete(): void
	{
		$file = TestFiles::all()->first();

		TestFiles::upload($file);
		Olaf::fingerprint($file);

		Olaf::delete($file);

		$this->assertEquals(0, Olaf::stats()->numberOfSongs);
	}

	public function testCreatingTrackIdentifiesDuplicate(): void
	{
		$source = TestFiles::all()->first();
		$extension = pathinfo($source, PATHINFO_EXTENSION);
		$content = file_get_contents("./tests/Fixtures/media/$source");
		$original = Track::factory()->uploaded($source)->create();

		// NB: Need to slightly modify the file sot hat it doesn't have an identical hash
		$modified = tempnam(sys_get_temp_dir(), 'duplicate').".$extension";

		file_put_contents($modified, $content);

		$audio = Audio::read($modified);

		$comment = 'Make it so that this duplicate file does not have the same hash as the original';

		$audio
			->write()
			->comment($comment)
			->save();

		$audio = Audio::read($modified);

		$this->assertEquals($comment, $audio->getComment());

		$content = file_get_contents($modified);

		// NB: Now do the actual form filling and upload
		$upload = UploadedFile::fake()->createWithContent('duplicate.mp3', $content);

		$response = Livewire::test(CreateTrack::class)
			->fillForm([
				'attachment' => $upload,
			])
			->call('create')
			->assertNotified()
			->assertRedirect();

		$url = $response->effects['redirect'];

		if (! preg_match('/tracks\/(\d+)$/', $url, $m))
			$this->fail('Failed to get ID from redirect URL');

		$redirectId = (int) $m[1];

		// NB: Check the duplicaet is in the database
		$this->assertDatabaseHas(TrackHasDuplicates::class, [
			'original_id' => $original->id,
			'duplicate_id' => $redirectId,
		]);

		// NB: Check the mutual relationships
		$track = Track::findOrFail($redirectId);

		$this->assertEquals($track->id, $original->duplicates->first()->id);
		$this->assertEquals($track->originals->first()->id, $original->id);
	}

	public function testWarnsAboutDuplicateBeforeTrackIsCreated(): void
	{
		$source = TestFiles::all()->first();
		$extension = pathinfo($source, PATHINFO_EXTENSION);
		$content = file_get_contents("./tests/Fixtures/media/$source");

		Track::factory()->uploaded($source)->create();

		// NB: Need to slightly modify the file so that it doesn't have an identical hash, matching
		// how testCreatingTrackIdentifiesDuplicate does it above - we want Olaf's fuzzy audio match here, not the cheap hash check.
		$modified = tempnam(sys_get_temp_dir(), 'duplicate').".$extension";

		file_put_contents($modified, $content);

		$audio = Audio::read($modified);

		$audio
			->write()
			->comment('Make it so that this duplicate file does not have the same hash as the original')
			->save();

		$content = file_get_contents($modified);

		$upload = UploadedFile::fake()->createWithContent('pre-save-duplicate.'.$extension, $content);

		// NB: fillForm() deliberately suppresses afterStateUpdated() for file uploads during testing,
		// so set() is used directly here to simulate what a real browser upload triggers.
		Livewire::test(CreateTrack::class)
			->set('data.attachment', $upload);

		Notification::assertNotified('Possible duplicate track found');

		// NB: This is the crux of the feature - the warning must fire before the record exists.
		$this->assertDatabaseMissing(Track::class, [
			'path' => 'pre-save-duplicate.'.$extension,
		]);

		$leftovers = collect(Storage::disk('media')->allFiles())
			->filter(fn (string $path) => str_starts_with(basename($path), '.duplicate-check-'));

		$this->assertCount(0, $leftovers);
	}

	public function testDeletingTrackRemovesFromOlaf(): void
	{
		$track = Track::factory()->uploaded()->create();

		$this->assertEquals(1, Olaf::stats()->numberOfSongs);

		$track->delete();

		$this->assertEquals(0, Olaf::stats()->numberOfSongs);
	}
}
