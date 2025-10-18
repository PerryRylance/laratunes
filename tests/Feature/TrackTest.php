<?php

namespace Tests\Feature;

use App\Filament\Resources\Tracks\Pages\ListTracks;
use App\Models\Track;
use App\Models\User;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Tests\TestCase;

class TrackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function testIndex(): void
    {
        $tracks = Track::factory()->count(3)->create();

        Livewire::test(ListTracks::class)
            ->assertOk()
            ->assertCanSeeTableRecords($tracks);
    }

    public function testSearchByTitleAndArtist(): void
    {
        $tracks = Track::factory()->count(5)->create();

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
        $tracks = Track::factory()->count(5)->create();

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
        $tracks = Track::factory()->count(5)->create();

        Livewire::test(ListTracks::class)
            ->assertCanSeeTableRecords($tracks)
            ->sortTable('plays')
            ->assertCanSeeTableRecords($tracks->sortBy('plays'), inOrder: true)
            ->sortTable('plays', 'desc')
            ->assertCanSeeTableRecords($tracks->sortByDesc('plays'), inOrder: true);
    }

    public function testSortByLastPlayed(): void
    {
        $tracks = Track::factory()->count(5)->create();

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

    public function testCreate(): void
    {

    }

    public function testCreateReadsMetadata(): void
    {

    }

    public function testCreateIdentifiesDuplicate(): void
    {

    }

    public function testCreateFailsWithUnsupportedFile(): void
    {

    }

    public function testView(): void
    {

    }

    public function testUpdateMetadata(): void
    {

    }

    public function testCannotUpdateFixedFields(): void
    {

    }

    public function testDelete(): void
    {

    }

    public function testBulkDelete(): void
    {
        $tracks = Track::factory()->count(5)->create();

        Livewire::test(ListTracks::class)
            ->assertCanSeeTableRecords($tracks)
            ->selectTableRecords($tracks)
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
            ->assertNotified()
            ->assertCanNotSeeTableRecords($tracks);

        $tracks->each(fn (Track $track) => $this->assertDatabaseMissing($track));
    }

    public function testDeleteRemovesFile(): void
    {

    }

    public function testDeleteRemovesVotes(): void
    {

    }
}
