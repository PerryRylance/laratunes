<?php

namespace App\Jobs;

use App\Models\Track;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class CreateTrackJob implements ShouldQueue
{
	use Queueable;

	/**
	 * Create a new job instance.
	 */
	public function __construct(public readonly string $path) {}

	/**
	 * Execute the job.
	 */
	public function handle(): void
	{
		if (! Storage::disk('media')->exists($this->path))
			throw new FileNotFoundException();

		Track::createFromFile($this->path);
	}
}
