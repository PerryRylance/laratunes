<?php

namespace Tests\Integration;

use App\Facades\BroadcastSupervisor;
use App\Facades\Fifo;
use App\Facades\Transmission;
use App\Models\Setting;
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

		// NB: Settings weren't being seeded here at all, so Setting::isFullyConfigured() was
		// always false and the buffer/transmission loops bailed out immediately without ever
		// touching the FIFO - points transmission at the rtmp-sink container that's already part
		// of the dev/test compose stack, so it has somewhere real to connect to
		Setting::factory()->pairs([
			Setting::BROADCAST_VIDEO_WIDTH => 1280,
			Setting::BROADCAST_VIDEO_HEIGHT => 720,
			Setting::BROADCAST_BACKGROUND_PATH => 'loop.mp4',
			Setting::STREAM_URL => 'rtmp://rtmp-sink/live',
			Setting::STREAM_KEY => 'test',
		])->create();

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

		// NB: app:start-broadcast forks children via pcntl_fork() (Concurrency::driver('fork')),
		// which Symfony's Process never tracks - stop() only reaches the top-level artisan
		// process, so its fork children (buffering/transmission/monitor) and their own ffmpeg
		// children have to be swept up separately, or they leak for the container's lifetime and
		// pollute later tests (eg. Transmission::running() finding a stray transmitter process
		// still alive from here and reporting the broadcast as active). BroadcastSupervisor::stop()
		// is exactly this cleanup, reused rather than duplicated
		BroadcastSupervisor::stop();

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

	private function countMatchingProcesses(string $pattern): int
	{
		$output = shell_exec('ps -eo pid,args --width 1000');

		$count = 0;

		foreach (preg_split('/\r?\n/', trim($output)) as $line)
			if (preg_match($pattern, $line))
				$count++;

		return $count;
	}

	// NB: Regression test for the leak where restarting only the transmitter (eg. via the
	// "restart broadcast" button) left the previous buffering process running - since it treats
	// a dead ffmpeg as recoverable and just moves on to the next track - so every restart added
	// another buffering process writing into the same FIFO instead of replacing the old one
	public function testStartDoesNotLeaveOrphanedProcessesAfterThePreviousTransmitterDied(): void
	{
		$this->populateMedia();
		$this->startBroadcast();

		$this->assertTrue(BroadcastSupervisor::isFullyRunning());

		// NB: Simulate only the transmitter dying, as happened before this fix - the buffering
		// process and the rest of the previous supervisor tree are deliberately left alone here
		Transmission::restart();

		BroadcastSupervisor::start();

		$this->assertTrue(BroadcastSupervisor::isFullyRunning());

		$this->assertEquals(1, $this->countMatchingProcesses('/^\s*\d+\s+ffmpeg\b.*\/buffers\/now-playing\s*$/'));
		$this->assertEquals(1, $this->countMatchingProcesses('/^\s*\d+\s+ffmpeg\b.*-i\s+\/buffers\/now-playing\b/'));
	}
}
