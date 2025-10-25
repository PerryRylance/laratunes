<?php

namespace Tests\Unit;

use App\Models\Track;
use Tests\TestCase;

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
            'title' => null
        ]);

        $this->assertEquals("Unknown Artist - Unknown Title", $track->caption);
    }

    public function testAdminLink(): void
    {
        
    }
}
