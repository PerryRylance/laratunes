<?php

namespace Tests\Integration;

use App\Facades\Fifo;
use App\Models\Track;
use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;
use Tests\TestFiles;

class BroadcastTest extends TestCase
{
	private ?InvokedProcess $process;

	private function populateMedia(): void
	{
		// NB: Track and video loop to work with
		Track::factory()->uploaded()->create();
		TestFiles::upload('loop.mp4');

		// NB: Commit the pending transaction otherwise the following artisan commands are blocked by the database, I guess something is locked
		DB::commit();

		$this->assertEquals(0, DB::transactionLevel());

		// NB: Because the tests run in a transaction, factories won't work here. So make sure there's some tracks to broadcast with
		Process::run('php artisan app:reset-media');
		Process::run('php artisan app:discover-media --fake-olaf');
	}

	private function startBroadcast(): void
	{
		$this->process = Process::start('php artisan app:start-broadcast', fn (string $type, string $output) => print ("$type: $output".PHP_EOL));

		// NB: Give it a second to get the buffer going
		sleep(5);
	}

	protected function tearDown(): void
	{
		if (! empty($this->process))
			$this->process->stop(10, SIGKILL);

		parent::tearDown();
	}

	public function testNowPlayingBufferCreated(): void
	{
		$this->startBroadcast();

		$this->assertFileExists('/buffers/now-playing');
	}

	public function testBufferHasData(): void
	{
		$this->populateMedia();
		$this->startBroadcast();

		$this->assertGreaterThan(0, Fifo::usage());
	}
}
