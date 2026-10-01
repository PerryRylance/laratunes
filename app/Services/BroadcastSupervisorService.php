<?php

namespace App\Services;

use App\Exceptions\BroadcastSupervisorException;
use App\Facades\Buffer;
use App\Facades\Ffmpeg;
use App\Facades\Monitor;
use App\Facades\Transmission;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class BroadcastSupervisorService
{
	// NB: `artisan app:start-broadcast` forks its buffering, transmission and monitor children
	// with pcntl_fork(), which doesn't exec() a new process image - so every one of them keeps
	// the exact same command line as the master process. One pattern therefore matches the whole
	// tree, master and children alike, however many generations of it have been left running
	const SUPERVISOR_PROCESS_PATTERN = '/^\s*(\d+)\s+.*app:start-broadcast/';

	// NB: Deliberately a distinct pattern from the one above - the watchdog must never match
	// SUPERVISOR_PROCESS_PATTERN, or stop()/stopStrayChildren() would kill the very thing meant to
	// detect and recover from the broadcast being killed
	const WATCHDOG_PROCESS_PATTERN = '/^\s*(\d+)\s+.*app:supervise-broadcast/';

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

	// NB: Safe to call from inside the very process it's cleaning up after, unlike stop() -
	// excludes our own pid so a broadcast that's recovering from a failed fork task doesn't kill
	// itself, only whatever orphaned siblings/ffmpeg processes a previous failed iteration left behind
	public static function stopStrayChildren(): void
	{
		Log::info('Sweeping up stray broadcast processes');

		Ffmpeg::stop(static::SUPERVISOR_PROCESS_PATTERN, except: [(string) getmypid()]);

		Buffer::restart();
		Transmission::restart();
	}

	// NB: Gathered defensively (every field falls back to an explanation rather than throwing)
	// so a diagnostics call never itself becomes the reason a failure handler fails - this is
	// meant to run from inside exception handlers, including ones triggered by Redis being down
	public static function diagnostics(): array
	{
		return [
			'buffering' => static::safely(fn () => Buffer::isBuffering()),
			'transmitting' => static::safely(fn () => Transmission::isTransmitting()),
			'monitoring' => static::safely(fn () => Monitor::isRunning()),
			'cpu_percent' => static::safely(fn () => static::latest(MonitorService::list('monitor:cpu'))),
			'memory_used_mb' => static::safely(fn () => static::latest(MonitorService::list('monitor:memory'))),
			'memory_total_mb' => static::safely(fn () => MonitorService::getTotalMemory()),
			'fifo_bytes_available' => static::safely(fn () => static::latest(MonitorService::list('monitor:buffer'))),
			'transmitter_bitrate_kbps' => static::safely(fn () => static::latest(MonitorService::list('monitor:bitrate'))),
			'processes' => static::safely(fn () => trim(Process::run('ps -eo pid,ppid,%cpu,%mem,etime,args --width 1000')->output())),
		];
	}

	private static function latest(array $history): mixed
	{
		return $history ? $history[array_key_last($history)] : null;
	}

	private static function safely(Closure $callback): mixed
	{
		try
		{
			return $callback();
		}
		catch (Throwable $e)
		{
			return 'unavailable: '.$e->getMessage();
		}
	}

	public static function start(): void
	{
		static::stop();

		Log::info('Starting broadcast supervisor');

		Process::path(base_path())->run('nohup php artisan app:start-broadcast > storage/logs/broadcast.log 2>&1 &');

		static::ensureWatchdogRunning();

		static::waitUntilFullyRunning();
	}

	// NB: Must run in the same container as app:start-broadcast - isFullyRunning() et al. find
	// their processes via `ps` in the local pid namespace, so a watchdog running anywhere else
	// would never see the broadcast as up and would keep trying to start duplicates of it.
	// Piggybacking on start() keeps it co-located without needing a separate scheduled process
	private static function ensureWatchdogRunning(): void
	{
		if (Ffmpeg::isRunning(static::WATCHDOG_PROCESS_PATTERN))
		return;

		Log::info('Starting broadcast watchdog');

		Process::path(base_path())->run('nohup php artisan app:supervise-broadcast > storage/logs/broadcast-watchdog.log 2>&1 &');
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
