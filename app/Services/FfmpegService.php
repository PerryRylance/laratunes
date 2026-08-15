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

	// NB: Finds ffmpeg processes whose command line matches $pattern and kills them, so a
	// stuck buffer or transmission can be recovered without the caller needing to know its pid
	public static function stop(string $pattern): void
	{
		$output = Process::run('ps -eo pid,args --width 1000')->output();

		foreach (preg_split('/\r?\n/', trim($output)) as $line)
		{
			if (! preg_match($pattern, $line, $m))
				continue;

			Process::run(['kill', '-9', $m[1]]);
		}
	}

	// private static function report(string $name, string $)
}
