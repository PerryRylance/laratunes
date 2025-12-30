<?php

namespace App\Contracts;

use App\Models\Track;
use chillerlan\QRCode\QRCode;

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
