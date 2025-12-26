<?php

namespace Tests;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

final class TestFiles
{
    public static function all(): Collection
    {
        return new Collection([
            '8-bit-takeover-367276.mp3',
			'chiptune-techno-electro-bubblegum-bass-bass-music-hiphop-1-334458.mp3',
            'pixelate-pixelated-dreams-313358.mp3'
        ]);
    }

    public static function upload(string $filename)
    {
        return Storage::disk('media')->put($filename, file_get_contents("./tests/Fixtures/media/$filename"));
    }
}
