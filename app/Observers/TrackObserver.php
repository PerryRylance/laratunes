<?php

namespace App\Observers;

use App\Models\Track;
use Illuminate\Support\Facades\Storage;
use Kiwilan\Audio\Audio;

class TrackObserver
{
    /**
     * Handle the Track "created" event.
     */
    public function creating(Track $track): void
    {
        $path = Storage::disk('media')->path($track->path);

        $track->hash = md5_file($path);

        $audio = Audio::read($path);

        $track->title = $audio->getTitle();
        $track->artist = $audio->getArtist();
    }
}
