<?php

namespace App\Services;

use Illuminate\Process\FakeInvokedProcess;
use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Redis;

class FfmpegService
{
	public static function start(string $name, array $params, int $priority = 0): InvokedProcess|FakeInvokedProcess
	{
		$lower = strtolower($name);

		return Process::timeout(false)->start([/* 'nice', '-n', $priority, */ 'ffmpeg', ...$params], function (string $type, string $output) use ($name, $lower) {

			if (preg_match('/bitrate=\s*(\d+(.\d+)?)kbits\/s/', $output, $m))
				Redis::set("monitor:bitrate:$lower", $m[1]);
			else Log::info("[$name].std$type: $output");

		});
	}

	public static function getDuration(string $path): int
	{
		$output = Process::run([
			'ffprobe',
			'-v', 'error',
			'-show_entries', 'format=duration',
			'-of', 'default=noprint_wrappers=1:nokey=1',
			$path,
		])->output();

		return (int) round((float) trim($output));
	}

	// NB: Finds processes whose command line matches $pattern and kills them, so a stuck buffer
	// or transmission can be recovered without the caller needing to know its pid. Despite the
	// name, the pattern isn't required to match ffmpeg specifically - BroadcastSupervisorService
	// reuses this to tear down stray `artisan app:start-broadcast` processes too
	public static function stop(string $pattern): void
	{
		foreach (static::matchingPids($pattern) as $pid)
			Process::run(['kill', '-9', $pid]);
	}

	public static function isRunning(string $pattern): bool
	{
		return count(static::matchingPids($pattern)) > 0;
	}

	private static function matchingPids(string $pattern): array
	{
		$output = Process::run('ps -eo pid,args --width 1000')->output();

		$pids = [];

		foreach (preg_split('/\r?\n/', trim($output)) as $line)
			if (preg_match($pattern, $line, $m))
				$pids[] = $m[1];

		return $pids;
	}

	// private static function report(string $name, string $)
}
