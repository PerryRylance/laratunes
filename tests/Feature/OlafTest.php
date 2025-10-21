<?php

namespace Tests\Feature;

use App\Filament\Resources\Tracks\Pages\CreateTrack;
use App\Models\Track;
use App\Models\TrackHasDuplicates;
use App\Facades\Olaf;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Attributes\UsesRealOlaf;
use Tests\Attributes\UsesRealStorage;

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
        Olaf::store('8-bit-takeover-367276.mp3');
        Olaf::reset();

        $this->assertFileDoesNotExist('./.olaf/docker_dbs/db/data.mdb');
        $this->assertFileDoesNotExist('./.olaf/docker_dbs/db/lock.mdb');
    }

    public function testStats(): void
    {
        // NB: Store one file first otherwise it'll error because the Olaf DB doesn't exist
        Olaf::store('8-bit-takeover-367276.mp3');

        $stats = Olaf::stats();

        $this->assertEquals(0, $stats->databaseFileSizeInMb);
        $this->assertEquals(1, $stats->numberOfSongs);
    }

    public function testStore(): void
    {
        $this->assertEquals(0, Olaf::stats()->numberOfSongs);

        Olaf::store('8-bit-takeover-367276.mp3');

        $this->assertEquals(1, Olaf::stats()->numberOfSongs);
    }

    public function testQuery(): void
    {
        $expected = 'chiptune-techno-electro-bubblegum-bass-bass-music-hiphop-1-334458.mp3';

        Olaf::store('8-bit-takeover-367276.mp3');
        Olaf::store($expected);
        Olaf::store('pixelate-pixelated-dreams-313358.mp3');

        copy("./tests/Fixtures/media/$expected", './tests/Fixtures/media/query.mp3');

        $results = Olaf::query('query.mp3');

        unlink('./tests/Fixtures/media/query.mp3');

        $best = $results->items->first();

        $this->assertEquals($expected, $best['file']);
        $this->assertEquals(988, $best['confidence']);
    }

    public function testQueryDoesNotYieldFalsePositives(): void
    {
        Olaf::store('8-bit-takeover-367276.mp3');
        Olaf::store('pixelate-pixelated-dreams-313358.mp3');

        $results = Olaf::query('chiptune-techno-electro-bubblegum-bass-bass-music-hiphop-1-334458.mp3');

        $this->assertCount(0, $results->items);
    }

    public function testDelete(): void
    {
        Olaf::store('8-bit-takeover-367276.mp3');
        Olaf::delete('8-bit-takeover-367276.mp3');

        $this->assertEquals(0, Olaf::stats()->numberOfSongs);
    }

    public function testCreatingTrackIdentifiesDuplicate(): void
    {
        $this->beforeApplicationDestroyed(fn() => unlink('./tests/Fixtures/media/duplicate.mp3'));

        $source = '8-bit-takeover-367276.mp3';

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

        $this->assertDatabaseHas(TrackHasDuplicates::class, [
            'original_id' => $original->id,
            'duplicate_id' => $m[1]
        ]);
    }
}
