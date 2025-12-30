<?php

namespace App\Services;

use App\Contracts\NowPlayingContract;
use LogicException;

class NowPlayingService extends NowPlayingContract
{
	protected static function path(): string
	{
		throw new LogicException('Not yet implemented');
	}
}
