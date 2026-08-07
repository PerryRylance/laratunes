<?php

namespace Tests\Unit\Jobs;

use App\Facades\Olaf;
use App\Jobs\DeleteTrackJob;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteTrackJobTest extends TestCase
{
	public function testHandleCallsOlafDeleteWithTheFilename(): void
	{
		$path = 'fake/path.mp3';

		Olaf::shouldReceive('delete')->once()->with($path);

		(new DeleteTrackJob($path))->handle();
	}

	public function testHandleDeletesTheFile(): void
	{
		Storage::disk('media')->put('fake/path.mp3', 'content');

		(new DeleteTrackJob('fake/path.mp3'))->handle();

		$this->assertFalse(Storage::disk('media')->exists('fake/path.mp3'));
	}
}
