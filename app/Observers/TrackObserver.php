<?php

namespace App\Observers;

use App\Facades\Olaf;
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

		if (file_exists($path))
			$track->hash = md5_file($path);

		if ($track->title === null || $track->artist === null)
		{
			$audio = Audio::read($path);

			// NB: Attempt to get title and artist from the metadata if not already specified. The interface will try this first - tests will populate this though.
			if ($track->title === null)
				$track->title = $audio->getTitle();

			if ($track->artist === null)
				$track->artist = $audio->getArtist();
		}
	}

	public function created(Track $track): void
	{
		// NB: Give this more headroom than the Guzzle timeout so Guzzle - not PHP's own execution
		// limit - is what decides when a slow Olaf request gives up.
		set_time_limit(config('olaf.timeout') + 30);

		Olaf::fingerprint($track->path);

		$duplicates = Olaf::query($track->path);

		if ($duplicates->items->isEmpty())
		return;

		$paths = $duplicates->items->pluck('file')->unique();
		$tracks = Track::whereIn('path', $paths)->get();

		foreach ($tracks as $original)
			$original->duplicates()->attach($track);
	}

	public function deleting(Track $track): void
	{
		Olaf::delete($track->path);
		Storage::disk('media')->delete($track->path);
	}
}
