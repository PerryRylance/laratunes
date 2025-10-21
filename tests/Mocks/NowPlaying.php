<?php

namespace Tests\Mocks;

use App\Contracts\NowPlayingContract;

class NowPlaying extends NowPlayingContract
{
    protected static function path(): string
    {
        return "./tests/Fixtures/screenshot.jpg";
    }
}
