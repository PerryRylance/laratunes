<?php

namespace App\Services;

use App\Exceptions\BroadcastSupervisorException;
use App\Facades\Buffer;
use App\Facades\Ffmpeg;
use App\Facades\Monitor;
use App\Facades\Transmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class BroadcastSupervisorService
{
	// NB: `artisan app:start-broadcast` forks its buffering, transmission and monitor children
	// with pcntl_fork(), which doesn't exec() a new process image - so every one of them keeps
	// the exact same command line as the master process. One pattern therefore matches the whole
	// tree, master and children alike, however many generations of it have been left running
	const SUPERVISOR_PROCESS_PATTERN = '/^\s*(\d+)\s+.*app:start-broadcast/';

	// NB: Order matters here. The supervisor (master + forked children) is killed first, so
	// nothing is left alive that could spawn another ffmpeg process while we clean up, then any
	// ffmpeg processes left behind - orphaned, since killing a forked PHP child doesn't kill its
	// own ffmpeg child - are swept up by pattern. Without this, restarting only the transmission
	// (as the "restart broadcast" button used to) left the previous buffering process running
	// forever, since it treats a dead ffmpeg as recoverable and just moves on to the next track
	public static function stop(): void
	{
		Log::info('Stopping broadcast supervisor');

		Ffmpeg::stop(static::SUPERVISOR_PROCESS_PATTERN);

		Buffer::restart();
		Transmission::restart();
	}

	public static function start(): void
	{
		static::stop();

		Log::info('Starting broadcast supervisor');

		Process::path(base_path())->run('nohup php artisan app:start-broadcast > storage/logs/broadcast.log 2>&1 &');

		static::waitUntilFullyRunning();
	}

	public static function isFullyRunning(): bool
	{
		return Buffer::isBuffering() && Transmission::isTransmitting() && Monitor::isRunning();
	}

	// NB: Checks each service exactly once per attempt (rather than going through
	// isFullyRunning(), which short-circuits) so the final state used for the exception message
	// below is always the state of the very last check, not a stale re-check
	private static function waitUntilFullyRunning(): void
	{
		$timeout = config('broadcast.startup_timeout');

		for ($elapsed = 0; $elapsed <= $timeout; $elapsed++)
		{
			$buffering = Buffer::isBuffering();
			$transmitting = Transmission::isTransmitting();
			$monitoring = Monitor::isRunning();

			if ($buffering && $transmitting && $monitoring)
			return;

			if ($elapsed < $timeout)
				sleep(1);
		}

		throw new BroadcastSupervisorException(sprintf(
			'Broadcast did not start cleanly (buffering: %s, transmitting: %s, monitoring: %s)',
			$buffering ? 'yes' : 'no',
			$transmitting ? 'yes' : 'no',
			$monitoring ? 'yes' : 'no',
		));
	}
}
