<?php

namespace App\Jobs;

use App\Facades\Olaf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DeleteTrackJob implements ShouldQueue
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
		Olaf::delete($this->path);
		Storage::disk('media')->delete($this->path);
	}
}
