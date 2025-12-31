<?php

namespace App\Contracts;

use App\Models\Track;
use chillerlan\QRCode\QRCode;

// TODO: Not sure we really need this any more, we can use a Nightbot timestamp and last_played_at
abstract class NowPlayingContract
{
	abstract protected static function path(): string;

	private static function hash(): string
	{
		$result = (new QRCode)->readFromFile(static::path());

		return (string) $result;
	}

	public static function track(): Track
	{
		return Track::whereHash(static::hash())->firstOrFail();
	}
}
