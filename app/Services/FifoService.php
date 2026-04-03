<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class FifoService
{
	private static $keepOpenHandle;

	private static int $capacity;

	public static function create(string $file): void
	{
		$result = Process::run("rm -f $file && mkfifo $file");

		if ($result->failed())
		{
			$output = trim($result->errorOutput());
			$message = "Failed to create now playing buffer ($output)";

			throw new \Exception($message);
		}

		$result = Process::run('set_fifo_size');
		$output = $result->output();

		if ($result->failed())
			throw new \Exception("Failed to set FIFO size: {$output} ({$result->exitCode()})");

		if (! preg_match('/FIFO buffer size successfully set to (\d+) bytes/', $output, $m))
			throw new \Exception("Failed to match created FIFO size in $output");

		static::$capacity = (int) $m[1];

		Log::info("Created FIFO at $file with ".static::$capacity.' bytes capacity');
	}

	public static function capacity(): int
	{
		return static::$capacity;
	}

	public static function usage(): int
	{
		$result = Process::run('get_fifo_bytes_available');
		$output = $result->output();

		return (int) $output;
	}
}
