<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Collection;

class FfmpegException extends Exception
{
	public readonly string $fullOutput;

	public function __construct(InvokedProcess $process, string $message = '', int $code = 0, ?\Throwable $previous = null)
	{
		$output = $process->errorOutput();
		$lines = new Collection(explode(PHP_EOL, $output));
		$errors = $lines->filter(fn (string $line) => preg_match('/^Error /', $line));

		if ($errors->count())
			$message .= PHP_EOL.$errors->count().' error(s):'.PHP_EOL.implode(PHP_EOL, $errors->toArray());
		else $message .= PHP_EOL.'Output:'.$output;

		parent::__construct($message, $code, $previous);
	}
}
