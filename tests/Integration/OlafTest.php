<?php

namespace Tests\Integration;

use App\Filament\Resources\Tracks\Pages\CreateTrack;
use App\Models\Track;
use App\Models\TrackHasDuplicates;
use App\Facades\Olaf;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Attributes\UsesRealOlaf;
use Tests\Attributes\UsesRealStorage;
use Tests\TestFiles;

#[UsesRealOlaf]
#[UsesRealStorage]
class OlafTest extends TestCase
{
    protected function afterRefreshingDatabase()
    {
        Olaf::reset();
    }

    public function testReset(): void
    {
        Olaf::store( TestFiles::all()->first() );
        Olaf::reset();

        $this->assertFileDoesNotExist('./.olaf/docker_dbs/db/data.mdb');
        $this->assertFileDoesNotExist('./.olaf/docker_dbs/db/lock.mdb');
    }

    public function testStats(): void
    {
        // NB: Store one file first otherwise it'll error because the Olaf DB doesn't exist
        Olaf::store( TestFiles::all()->first() );

        $stats = Olaf::stats();

        $this->assertEquals(0, $stats->databaseFileSizeInMb);
        $this->assertEquals(1, $stats->numberOfSongs);
    }

    public function testStore(): void
    {
        $this->assertEquals(0, Olaf::stats()->numberOfSongs);

        Olaf::store( TestFiles::all()->first() );

        $this->assertEquals(1, Olaf::stats()->numberOfSongs);
    }

    public function testQuery(): void
    {
        $files = TestFiles::all();
        $expected = $files->take(1)->first();
        $others = $files->slice(1);

        Olaf::store($expected);

        foreach($others as $other)
            Olaf::store($other);

        copy("./tests/Fixtures/media/$expected", './tests/Fixtures/media/query.mp3');

        $results = Olaf::query('query.mp3');

        unlink('./tests/Fixtures/media/query.mp3');

        $best = $results->items->first();

        $this->assertEquals($expected, $best['file']);
        $this->assertEquals(641, $best['confidence']);
    }

    public function testQueryDoesNotYieldFalsePositives(): void
    {
        $files = TestFiles::all();
        $unexpected = $files->take(1)->first();
        $others = $files->slice(1);

        foreach($others as $other)
            Olaf::store($other);

        $results = Olaf::query($unexpected);

        $this->assertCount(0, $results->items);
    }

    public function testDelete(): void
    {
        Olaf::store( TestFiles::all()->first() );
        Olaf::delete( TestFiles::all()->first() );

        $this->assertEquals(0, Olaf::stats()->numberOfSongs);
    }

    // TODO: I am flakey when running the whole suite
    public function testCreatingTrackIdentifiesDuplicate(): void
    {
        $this->beforeApplicationDestroyed(fn() => unlink('./tests/Fixtures/media/duplicate.mp3'));

        $source = TestFiles::all()->first();

        $original = Track::factory()->create([
            'path' => $source
        ]);

        $content = file_get_contents("./tests/Fixtures/media/$source");

        $file = UploadedFile::fake()->createWithContent('duplicate.mp3', $content);

        $response = Livewire::test(CreateTrack::class)
            ->fillForm([
                'attachment' => $file
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();
        
        $url = $response->effects['redirect'];

        if(!preg_match('/tracks\/(\d+)$/', $url, $m))
            $this->fail('Failed to get ID from redirect URL');

        $redirectId = (int)$m[1];

        // NB: Check the duplicaet is in the database
        $this->assertDatabaseHas(TrackHasDuplicates::class, [
            'original_id' => $original->id,
            'duplicate_id' => $redirectId
        ]);

        // NB: Check the mutual relationships
        $track = Track::findOrFail($redirectId);

        $this->assertEquals($track->id, $original->duplicates->first()->id);
        $this->assertEquals($track->originals->first()->id, $original->id);
    }

    public function testDeletingTrackRemovesFromOlaf(): void
    {
        $track = Track::factory()->uploaded()->create();

        $this->assertEquals(1, Olaf::stats()->numberOfSongs);

        $track->delete();

        $this->assertEquals(0, Olaf::stats()->numberOfSongs);
    }
}
