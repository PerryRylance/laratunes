<?php

namespace Tests\Feature;

use App\Contracts\OlafContract;
use App\Facades\Olaf;
use App\Filament\Resources\Tracks\Pages\CreateTrack;
use App\Filament\Resources\Tracks\Pages\EditTrack;
use App\Filament\Resources\Tracks\Pages\ListTracks;
use App\Filament\Resources\Tracks\Pages\ViewTrack;
use App\Filament\Resources\Tracks\RelationManagers\DuplicatesRelationManager;
use App\Filament\Resources\Tracks\RelationManagers\OriginalsRelationManager;
use App\Filament\Widgets\FileMissingCallout;
use App\Models\Track;
use App\Models\TrackHasDuplicates;
use App\Models\User;
use App\Support\Olaf\QueryResults;
use App\Support\Olaf\Stats;
use Carbon\Carbon;
use Filament\Actions\AttachAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\Notification;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\AdminTestCase;

class TrackTest extends AdminTestCase
{
	public function testIndex(): void
	{
		$tracks = Track::factory()->uploaded()->count(3)->create();

		Livewire::test(ListTracks::class)
			->assertOk()
			->assertCanSeeTableRecords($tracks);
	}

	public function testSearchByTitleAndArtist(): void
	{
		$tracks = Track::factory()->uploaded()->count(3)->create();

		Livewire::test(ListTracks::class)
			->assertCanSeeTableRecords($tracks)
			->searchTable($tracks->first()->title)
			->assertCanSeeTableRecords($tracks->take(1))
			->assertCanNotSeeTableRecords($tracks->skip(1))
			->searchTable($tracks->last()->artist)
			->assertCanSeeTableRecords($tracks->take(-1))
			->assertCanNotSeeTableRecords($tracks->take($tracks->count() - 1));
	}

	public function testSortByTitleAndArtist(): void
	{
		$tracks = Track::factory()->uploaded()->count(3)->create();

		Livewire::test(ListTracks::class)
			->assertCanSeeTableRecords($tracks)
			->sortTable('title')
			->assertCanSeeTableRecords($tracks->sortBy('title'), inOrder: true)
			->sortTable('title', 'desc')
			->assertCanSeeTableRecords($tracks->sortByDesc('title'), inOrder: true)
			->sortTable('artist')
			->assertCanSeeTableRecords($tracks->sortBy('artist'), inOrder: true)
			->sortTable('artist', 'desc')
			->assertCanSeeTableRecords($tracks->sortByDesc('artist'), inOrder: true);
	}

	public function testSortByPlayCount(): void
	{
		$tracks = Track::factory()->uploaded()->count(3)->create();

		Livewire::test(ListTracks::class)
			->assertCanSeeTableRecords($tracks)
			->sortTable('plays')
			->assertCanSeeTableRecords($tracks->sortBy('plays'), inOrder: true)
			->sortTable('plays', 'desc')
			->assertCanSeeTableRecords($tracks->sortByDesc('plays'), inOrder: true);
	}

	public function testSortByLastPlayed(): void
	{
		$tracks = Track::factory()->uploaded()->count(3)->create();

		Livewire::test(ListTracks::class)
			->assertCanSeeTableRecords($tracks)
			->sortTable('last_played_at')
			->assertCanSeeTableRecords($tracks->sortBy('last_played_at'), inOrder: true)
			->sortTable('last_played_at', 'desc')
			->assertCanSeeTableRecords($tracks->sortByDesc('last_played_at'), inOrder: true);
	}

	public function testSortByUpVotes(): void
	{
		$this->markTestIncomplete('Voting not yet implemented');
	}

	public function testSortByDownVotes(): void
	{
		$this->markTestIncomplete('Voting not yet implemented');
	}

	public function testLoadCreatePage(): void
	{
		Livewire::test(CreateTrack::class)
			->assertOk();
	}

	public function testWarnsAboutPossibleDuplicateBeforeCreate(): void
	{
		// NB: Tests\Mocks\Olaf::query() always reports a match against 'fake.mp3' regardless of input,
		// so a Track at that path is what makes this scenario resolve to a real duplicate.
		Track::factory()->uploaded(dst: 'fake.mp3')->create();

		$file = UploadedFile::fake()->create('new-upload.mp3', 100, 'audio/mpeg');

		// NB: fillForm() deliberately suppresses afterStateUpdated() for file uploads during testing,
		// so set() is used directly here to simulate what a real browser upload triggers.
		Livewire::test(CreateTrack::class)
			->set('data.attachment', $file);

		Notification::assertNotified('Possible duplicate track found');

		$this->assertDatabaseMissing(Track::class, [
			'path' => 'new-upload.mp3',
		]);
	}

	public function testDoesNotWarnWhenNoDuplicateMatchExists(): void
	{
		$file = UploadedFile::fake()->create('new-upload.mp3', 100, 'audio/mpeg');

		Livewire::test(CreateTrack::class)
			->set('data.attachment', $file);

		Notification::assertNotNotified('Possible duplicate track found');
	}

	public function testDuplicateCheckCleansUpItsScratchFile(): void
	{
		Track::factory()->uploaded(dst: 'fake.mp3')->create();

		$file = UploadedFile::fake()->create('new-upload.mp3', 100, 'audio/mpeg');

		Livewire::test(CreateTrack::class)
			->set('data.attachment', $file);

		$leftovers = collect(Storage::disk('media')->allFiles())
			->filter(fn (string $path) => str_starts_with(basename($path), '.duplicate-check-'));

		$this->assertCount(0, $leftovers);
	}

	public function testDuplicateCheckFailsOpenWhenOlafIsUnreachable(): void
	{
		Olaf::swap(new class implements OlafContract {
			public static function reset(): void {}

			public static function stats(): Stats
			{
				return new Stats('');
			}

			public static function fingerprint(string $filename): void {}

			public static function query(string $filename): QueryResults
			{
				throw new RuntimeException('Simulated Olaf outage');
			}

			public static function delete(string $filename): void {}
		});

		$file = UploadedFile::fake()->create('new-upload.mp3', 100, 'audio/mpeg');

		Livewire::test(CreateTrack::class)
			->set('data.attachment', $file)
			->assertOk();

		Notification::assertNotNotified('Possible duplicate track found');
	}

	public function testCreate(): void
	{
		$file = UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg');

		Livewire::test(CreateTrack::class)
			->fillForm([
				'attachment' => $file,
			])
			->call('create')
			->assertNotified()
			->assertRedirect();

		$this->assertTrue(Storage::disk('media')->exists('test.mp3'));

		$this->assertDatabaseHas(Track::class, [
			'path' => 'test.mp3',
			'hash' => md5(''),
		]);
	}

	private function testCreateReadsMetadata(string $source, array $expected): void
	{
		$content = file_get_contents($source);
		$filename = basename($source);

		$file = UploadedFile::fake()->createWithContent($filename, $content);

		Livewire::test(CreateTrack::class)
			->fillForm([
				'attachment' => $file,
			])
			->call('create')
			->assertNotified()
			->assertRedirect();

		$this->assertTrue(Storage::disk('media')->exists($filename));

		$this->assertDatabaseHas(Track::class, [
			'path' => $filename,
			'hash' => md5($content),
			...$expected,
		]);
	}

	public function testCreateReadsMp3Metadata(): void
	{
		$this->testCreateReadsMetadata('./tests/Fixtures/media/metadata.mp3', [
			'title' => 'Test MP3 Title',
			'artist' => 'Test MP3 Artist',
		]);
	}

	public function testCreateReadsOggMetadata(): void
	{
		$this->testCreateReadsMetadata('./tests/Fixtures/media/metadata.ogg', [
			'title' => 'Test OGG Title',
			'artist' => 'Test OGG Artist',
		]);
	}

	public function testCreateReadsFlacMetadata(): void
	{
		$this->testCreateReadsMetadata('./tests/Fixtures/media/metadata.flac', [
			'title' => 'Test FLAC Title',
			'artist' => 'Test FLAC Artist',
		]);
	}

	public function testCreateFailsWithUnsupportedFile(): void
	{
		$source = './tests/Fixtures/media/unsupported.media';

		$content = file_get_contents($source);
		$filename = basename($source);

		$file = UploadedFile::fake()->createWithContent($filename, $content);

		Livewire::test(CreateTrack::class)
			->fillForm([
				'attachment' => $file,
			])
			->call('create')
			->assertNotNotified()
			->assertNoRedirect();

		$this->assertFalse(Storage::disk('media')->exists($filename));

		$this->assertDatabaseMissing(Track::class, [
			'path' => $filename,
		]);
	}

	public function testCreateFailsWithNonUniqueHash(): void
	{
		$existing = Track::factory()->uploaded('test.mp3')->create();
		$content = file_get_contents('./tests/Fixtures/media/test.mp3');
		$file = UploadedFile::fake()->createWithContent(fake()->uuid().'.mp3', $content);

		Livewire::test(CreateTrack::class)
			->fillForm([
				'attachment' => $file,
			])
			->call('create')
			->assertHasFormErrors(['attachment']);
	}

	public function testCreateChangesFilenameWhenAlreadyExists(): void
	{
		$existing = Track::factory()->uploaded()->create();
		$content = file_get_contents('./tests/Fixtures/media/test.mp3');
		$file = UploadedFile::fake()->createWithContent($existing->path, $content);

		$response = Livewire::test(CreateTrack::class)
			->fillForm([
				'attachment' => $file,
			])
			->call('create')
			->assertNotified()
			->assertRedirect();

		$url = $response->effects['redirect'];

		if (! preg_match('/tracks\/(\d+)$/', $url, $m))
			$this->fail('Failed to get ID from redirect URL');

		$track = Track::findOrFail((int) $m[1]);

		$this->assertNotEquals($existing->path, $track->path);
		$this->assertEquals("{$existing->path} (1)", $track->path);

		$this->assertTrue(Storage::disk('media')->exists($track->path));
	}

	public function testView(): void
	{
		$track = Track::factory()->uploaded()->create();

		Livewire::test(ViewTrack::class, [
			'record' => $track->id,
		])
			->assertOk()
			->assertSchemaStateSet([
				'title' => $track->title,
				'artist' => $track->artist,
				'plays' => $track->plays,
				'path' => $track->path,
				'last_played_at' => $track->last_played_at?->format('Y-m-d H:i:s'),
				'created_at' => $track->created_at->format('Y-m-d\TH:i:s.u\Z'),
				'updated_at' => $track->updated_at->format('Y-m-d\TH:i:s.u\Z'),
			]);
	}

	public function testUpdateMetadata(): void
	{
		$track = Track::factory()->uploaded()->create();
		$data = Track::factory()->make()->only(['title', 'artist']);

		Livewire::test(EditTrack::class, [
			'record' => $track->id,
		])
			->fillForm($data)
			->call('save')
			->assertNotified();

		$this->assertDatabaseHas(Track::class, [
			'id' => $track->id,
			...$data,
		]);
	}

	public function testCannotUpdateFixedFields(): void
	{
		$track = Track::factory()->uploaded()->create();
		$hash = md5(time());

		Livewire::test(EditTrack::class, [
			'record' => $track->id,
		])
			->fillForm([
				'hash' => $hash,
			])
			->call('save')
			->assertNotified();

		$this->assertDatabaseMissing(Track::class, [
			'id' => $track->id,
			'hash' => $hash,
		]);
	}

	public function testDelete(): void
	{
		$track = Track::factory()->uploaded()->create();

		Livewire::test(EditTrack::class, [
			'record' => $track->id,
		])
			->callAction(DeleteAction::class)
			->assertNotified()
			->assertRedirect();

		$this->assertDatabaseMissing($track);
		$this->assertFalse(Storage::disk('media')->exists($track->path));
	}

	public function testBulkDelete(): void
	{
		$tracks = Track::factory()->uploaded()->count(3)->create();

		Livewire::test(ListTracks::class)
			->assertCanSeeTableRecords($tracks)
			->selectTableRecords($tracks)
			->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
			->assertNotified()
			->assertCanNotSeeTableRecords($tracks);

		$tracks->each(function (Track $track) {
			$this->assertDatabaseMissing($track);
			$this->assertFalse(Storage::disk('media')->exists($track->path));
		});
	}

	public function testDeleteRemovesVotes(): void
	{
		$this->markTestIncomplete('Voting not yet implemented');
	}

	public function testNextIncrementsPlayCount(): void
	{
		$track = Track::factory()->uploaded()->unplayed()->create();
		$actual = Track::next();

		$this->assertTrue($actual->is($track));

		$track->refresh();

		$this->assertEquals(1, $track->plays);
	}

	public function testNextSetsLastPlayedAt(): void
	{
		$track = Track::factory()->uploaded()->unplayed()->create();
		$actual = Track::next();
		$now = Carbon::now();

		$this->assertTrue($actual->is($track));

		$track->refresh();

		$this->assertLessThanOrEqual(1, $now->diffInSeconds($track->last_played_at, true));
	}

	public function testCanViewPath(): void
	{
		$track = Track::factory()->uploaded()->create();

		Livewire::test(ViewTrack::class, [
			'record' => $track->id,
		])
			->assertSee($track->path);
	}

	public function testCanGetAudioBinary(): void
	{
		$track = Track::factory()->uploaded()->create();
		$binary = Storage::disk('media')->get($track->path);

		$this
			->get("/api/audio/{$track->hash}", [
				'Range' => 'bytes=0-',
			])
			->assertStatus(Response::HTTP_PARTIAL_CONTENT)
			->assertHeader('Content-type', 'audio/mpeg')
			->assertContent($binary);
	}

	public function testGetAudioBinaryRespectsRequestedRange(): void
	{
		$track = Track::factory()->uploaded()->create();
		$binary = Storage::disk('media')->get($track->path);
		$expected = substr($binary, 0, 101);

		$this
			->get("/api/audio/{$track->hash}", [
				'Range' => 'bytes=0-100',
			])
			->assertStatus(Response::HTTP_PARTIAL_CONTENT)
			->assertHeader('Content-type', 'audio/mpeg')
			->assertContent($expected);
	}

	public function testUnauthorizedUsersCannotGetAudioBinary(): void
	{
		// NB: These tests run as admin by default so make a regular user here - this route isn't guarded by Filament's access functions (since it's not part of a panel) so we test this explicitly
		$user = User::factory()->create();
		$track = Track::factory()->uploaded()->create();
		Storage::disk('media')->get($track->path);

		$this->actingAs($user);

		$this
			->get("/api/audio/{$track->hash}", [
				'Range' => 'bytes=0-',
			])
			->assertStatus(Response::HTTP_FORBIDDEN);
	}

	public function testViewHasAudioPlayer(): void
	{
		$track = Track::factory()->uploaded()->create();

		Livewire::test(ViewTrack::class, [
			'record' => $track->id,
		])
			->assertElementPresent("audio[src='/api/audio/{$track->hash}']");
	}

	public function testViewShowsDuplicates(): void
	{
		// TODO: I really hate this "uploaded" pattern, can we not fake the hash in a service / facade? The only reason we have to keep doing this is because the observer wants to hash the actual file
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		Livewire::test(ViewTrack::class, [
			'record' => $original->id,
		])
			->assertSeeLivewire(DuplicatesRelationManager::class)
			->assertSee('Duplicates');
	}

	public function testSeeDuplicatesInTable(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		Livewire::test(DuplicatesRelationManager::class, [
			'ownerRecord' => $original,
			'pageClass' => ViewTrack::class,
		])
			->assertCanSeeTableRecords([$duplicate]);
	}

	public function testViewShowsOriginals(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		Livewire::test(ViewTrack::class, [
			'record' => $duplicate->id,
		])
			->set('activeRelationManager', 'originals')
			->assertSeeLivewire(OriginalsRelationManager::class)
			->assertSee('Originals');
	}

	public function testSeeOriginalsInTable(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		Livewire::test(OriginalsRelationManager::class, [
			'ownerRecord' => $duplicate,
			'pageClass' => ViewTrack::class,
		])
			->assertCanSeeTableRecords([$original]);
	}

	public function testCanSeeDuplicateCountInTable(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		$page = Livewire::test(ListTracks::class);

		$records = $page
			->toggleAllTableColumns()
			->instance()
			->getTableRecords();

		$recordWithCount = $records[0];

		$page->assertTableColumnFormattedStateSet(
			'duplicates_count',
			1,
			$recordWithCount
		);
	}

	public function testCanSeeOriginalsCountInTable(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		$page = Livewire::test(ListTracks::class);

		$records = $page
			->toggleAllTableColumns()
			->instance()
			->getTableRecords();

		$recordWithCount = $records[1];

		$page->assertTableColumnFormattedStateSet(
			'originals_count',
			1,
			$recordWithCount
		);
	}

	public function testCanSortByDuplicateCount(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		$asc = [$duplicate, $original];
		$desc = [$original, $duplicate];

		Livewire::test(ListTracks::class)
			->toggleAllTableColumns()
			->sortTable('duplicates_count')
			->assertCanSeeTableRecords($asc, inOrder: true)
			->sortTable('duplicates_count', 'desc')
			->assertCanSeeTableRecords($desc, inOrder: true);
	}

	public function testCanSortByOriginalsCount(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		$asc = [$original, $duplicate];
		$desc = [$duplicate, $original];

		Livewire::test(ListTracks::class)
			->toggleAllTableColumns()
			->sortTable('originals_count')
			->assertCanSeeTableRecords($asc, inOrder: true)
			->sortTable('originals_count', 'desc')
			->assertCanSeeTableRecords($desc, inOrder: true);
	}

	public function testCanFilterByHasDuplicates(): void
	{
		[$original, $duplicate] = $records = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		Livewire::test(ListTracks::class)
			->assertCanSeeTableRecords($records)
			->assertTableFilterExists('has_duplicates')
			->filterTable('has_duplicates')
			->assertCanSeeTableRecords([$original])
			->assertCanNotSeeTableRecords([$duplicate]);
	}

	public function testCanFilterByHasOriginals(): void
	{
		[$original, $duplicate] = $records = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		Livewire::test(ListTracks::class)
			->assertCanSeeTableRecords($records)
			->assertTableFilterExists('has_originals')
			->filterTable('has_originals')
			->assertCanSeeTableRecords([$duplicate])
			->assertCanNotSeeTableRecords([$original]);
	}

	public function testCanAttachDuplicatesManually(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		Livewire::test(DuplicatesRelationManager::class, [
			'ownerRecord' => $original,
			'pageClass' => EditTrack::class,
		])
			->callAction(TestAction::make(AttachAction::class)->table(), [
				'recordId' => $duplicate->id,
			])
			->assertHasNoErrors();

		$original->duplicates()->first()->is($duplicate);
	}

	public function testCanAttachOriginalsManually(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		Livewire::test(OriginalsRelationManager::class, [
			'ownerRecord' => $duplicate,
			'pageClass' => EditTrack::class,
		])
			->callAction(TestAction::make(AttachAction::class)->table(), [
				'recordId' => $original->id,
			])
			->assertHasNoErrors();

		$duplicate->originals()->first()->is($original);
	}

	public function testCanDetachDuplicatesManually(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		Livewire::test(DuplicatesRelationManager::class, [
			'ownerRecord' => $original,
			'pageClass' => EditTrack::class,
		])
			->callAction(TestAction::make(DetachAction::class)->table($duplicate))
			->assertHasNoErrors();

		$this->assertEquals(0, $original->duplicates()->count());
	}

	public function testCanDetachOriginalsManually(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		Livewire::test(OriginalsRelationManager::class, [
			'ownerRecord' => $duplicate,
			'pageClass' => EditTrack::class,
		])
			->callAction(TestAction::make(DetachAction::class)->table($original))
			->assertHasNoErrors();

		$this->assertEquals(0, $duplicate->originals()->count());
	}

	public function testCanBulkDetachDuplicatesManually(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		Livewire::test(DuplicatesRelationManager::class, [
			'ownerRecord' => $original,
			'pageClass' => EditTrack::class,
		])
			->selectTableRecords([$duplicate])
			->callAction(TestAction::make(DetachBulkAction::class)->table()->bulk())
			->assertNotified()
			->assertCanNotSeeTableRecords([$duplicate]);

		$this->assertDatabaseMissing(TrackHasDuplicates::getTableName(), [
			'original_id' => $original->id,
			'duplicate_id' => $duplicate->id,
		]);
	}

	public function testCanBulkDetachOriginalsManually(): void
	{
		[$original, $duplicate] = Track::factory()->uploaded()->count(2)->create();

		$original->duplicates()->attach($duplicate);

		Livewire::test(OriginalsRelationManager::class, [
			'ownerRecord' => $duplicate,
			'pageClass' => EditTrack::class,
		])
			->selectTableRecords([$original])
			->callAction(TestAction::make(DetachBulkAction::class)->table()->bulk())
			->assertNotified()
			->assertCanNotSeeTableRecords([$original]);

		$this->assertDatabaseMissing(TrackHasDuplicates::getTableName(), [
			'original_id' => $original->id,
			'duplicate_id' => $duplicate->id,
		]);
	}

	public function testSeeFileMissingCalloutOnView(): void
	{
		$track = Track::factory()->uploaded()->create();

		Storage::disk('media')->delete($track->path);

		Livewire::test(ViewTrack::class, [
			'record' => $track->id,
		])
			->assertOk()
			->assertSeeLivewire(FileMissingCallout::class);
	}

	public function testDontSeeFileMissingCalloutOnViewWhenFileExists(): void
	{
		$track = Track::factory()->uploaded()->create();

		Livewire::test(ViewTrack::class, [
			'record' => $track->id,
		])
			->assertOk()
			->assertDontSeeLivewire(FileMissingCallout::class);
	}
}
