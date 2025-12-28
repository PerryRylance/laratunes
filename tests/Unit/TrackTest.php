<?php

namespace Tests\Unit;

use App\Models\Track;
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
            'title' => null
        ]);

        $this->assertEquals("Unknown Artist - Unknown Title", $track->caption);
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

    public function testAdminLink(): void
    {
        
    }
}
