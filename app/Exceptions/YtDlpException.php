<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class YtDlpException extends Exception
{
	public function __construct(public readonly string $output, string $message = '', int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
