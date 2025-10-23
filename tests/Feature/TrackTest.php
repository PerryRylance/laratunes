<?php

namespace Tests\Feature;

use App\Filament\Resources\Tracks\Pages\CreateTrack;
use App\Filament\Resources\Tracks\Pages\ListTracks;
use App\Models\Track;
use App\Models\User;
use App\Facades\Olaf;
use App\Filament\Resources\Tracks\Pages\EditTrack;
use App\Filament\Resources\Tracks\Pages\ViewTrack;
use Carbon\Carbon;
use DateTime;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TrackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function testIndex(): void
    {
        $tracks = Track::factory()->uploaded()->count(3)->create();

        Livewire::test(ListTracks::class)
            ->assertOk()
            ->assertCanSeeTableRecords($tracks);
    }

    public function testSearchByTitleAndArtist(): void
    {
        $tracks = Track::factory()->uploaded()->count(5)->create();

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
        $tracks = Track::factory()->uploaded()->count(5)->create();

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
        $tracks = Track::factory()->uploaded()->count(5)->create();

        Livewire::test(ListTracks::class)
            ->assertCanSeeTableRecords($tracks)
            ->sortTable('plays')
            ->assertCanSeeTableRecords($tracks->sortBy('plays'), inOrder: true)
            ->sortTable('plays', 'desc')
            ->assertCanSeeTableRecords($tracks->sortByDesc('plays'), inOrder: true);
    }

    public function testSortByLastPlayed(): void
    {
        $tracks = Track::factory()->uploaded()->count(5)->create();

        Livewire::test(ListTracks::class)
            ->assertCanSeeTableRecords($tracks)
            ->sortTable('last_played_at')
            ->assertCanSeeTableRecords($tracks->sortBy('last_played_at'), inOrder: true)
            ->sortTable('last_played_at', 'desc')
            ->assertCanSeeTableRecords($tracks->sortByDesc('last_played_at'), inOrder: true);
    }

    public function testSortByUpVotes(): void
    {

    }

    public function testSortByDownVotes(): void
    {

    }

    public function testLoadCreatePage(): void
    {
        Livewire::test(CreateTrack::class)
            ->assertOk();
    }

    public function testCreate(): void
    {
        $file = UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg');

        Livewire::test(CreateTrack::class)
            ->fillForm([
                'attachment' => $file
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();
        
        $this->assertTrue(Storage::disk('media')->exists('test.mp3'));

        $this->assertDatabaseHas(Track::class, [
            'path' => 'test.mp3',
            'hash' => md5("")
        ]);
    }

    private function testCreateReadsMetadata(string $source, array $expected): void
    {
        $content = file_get_contents($source);
        $filename = basename($source);

        $file = UploadedFile::fake()->createWithContent($filename, $content);

        Livewire::test(CreateTrack::class)
            ->fillForm([
                'attachment' => $file
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();
        
        $this->assertTrue(Storage::disk('media')->exists($filename));

        $this->assertDatabaseHas(Track::class, [
            'path' => $filename,
            'hash' => md5($content),
            ...$expected
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
                'attachment' => $file
            ])
            ->call('create')
            ->assertNotNotified()
            ->assertNoRedirect();
        
        $this->assertFalse(Storage::disk('media')->exists($filename));

        $this->assertDatabaseMissing(Track::class, [
            'path' => $filename
        ]);
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
                'last_played_at' => $track->last_played_at?->format('Y-m-d H:i:s'),
                'created_at' => $track->created_at->format('Y-m-d\TH:i:s.u\Z'),
                'updated_at' => $track->updated_at->format('Y-m-d\TH:i:s.u\Z')
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
            ...$data
        ]);
    }

    public function testCannotUpdateFixedFields(): void
    {
        $track = Track::factory()->uploaded()->create();
        $hash = md5( time() );

        Livewire::test(EditTrack::class, [
            'record' => $track->id,
        ])
            ->fillForm([
                'hash' => $hash
            ])
            ->call('save')
            ->assertNotified();

        $this->assertDatabaseMissing(Track::class, [
            'id' => $track->id,
            'hash' => $hash
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
        $tracks = Track::factory()->uploaded()->count(5)->create();

        Livewire::test(ListTracks::class)
            ->assertCanSeeTableRecords($tracks)
            ->selectTableRecords($tracks)
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
            ->assertNotified()
            ->assertCanNotSeeTableRecords($tracks);

        $tracks->each(function(Track $track) {
            $this->assertDatabaseMissing($track);
            $this->assertFalse(Storage::disk('media')->exists($track->path));
        });
    }

    public function testDeleteRemovesVotes(): void
    {

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
}
