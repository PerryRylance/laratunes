<?php

namespace Tests\Feature;

use App\Support\Olaf;
use Tests\OlafTestCase;

class OlafTest extends OlafTestCase
{
    public function testReset(): void
    {
        $olaf = new Olaf();
        
        $olaf->store('8-bit-takeover-367276.mp3');
        $olaf->reset();

        $this->assertFileDoesNotExist('./.olaf/docker_dbs/db/data.mdb');
        $this->assertFileDoesNotExist('./.olaf/docker_dbs/db/lock.mdb');
    }

    public function testStats(): void
    {
        // NB: Store one file first otherwise it'll error because the Olaf DB doesn't exist
        $olaf = new Olaf();
        $olaf->store('8-bit-takeover-367276.mp3');

        $stats = $olaf->stats();

        $this->assertEquals(0, $stats->databaseFileSizeInMb);
        $this->assertEquals(1, $stats->numberOfSongs);
    }

    public function testStore(): void
    {
        $olaf = new Olaf();

        $this->assertEquals(0, $olaf->stats()->numberOfSongs);

        $olaf->store('8-bit-takeover-367276.mp3');

        $this->assertEquals(1, $olaf->stats()->numberOfSongs);
    }

    public function testQuery(): void
    {
        $expected = 'chiptune-techno-electro-bubblegum-bass-bass-music-hiphop-1-334458.mp3';

        $olaf = new Olaf();

        $olaf->store('8-bit-takeover-367276.mp3');
        $olaf->store($expected);
        $olaf->store('pixelate-pixelated-dreams-313358.mp3');

        copy("./tests/Fixtures/media/$expected", './tests/Fixtures/media/query.mp3');

        $results = $olaf->query('query.mp3');

        unlink('./tests/Fixtures/media/query.mp3');

        $best = $results->first();

        $this->assertEquals($expected, $best->file);
        $this->assertEquals(988, $best->confidence);
    }

    public function testQueryDoesNotYieldFalsePositives(): void
    {
        $olaf = new Olaf();

        $olaf->store('8-bit-takeover-367276.mp3');
        $olaf->store('pixelate-pixelated-dreams-313358.mp3');

        $results = $olaf->query('chiptune-techno-electro-bubblegum-bass-bass-music-hiphop-1-334458.mp3');

        $this->assertCount(0, $results);
    }

    public function testDelete(): void
    {
        $olaf = new Olaf();

        $olaf->store('8-bit-takeover-367276.mp3');
        $olaf->delete('8-bit-takeover-367276.mp3');

        $this->assertEquals(0, $olaf->stats()->numberOfSongs);
    }
}
