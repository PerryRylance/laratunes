<?php

namespace App\Support\Olaf;

use UnexpectedValueException;

class Stats
{
	public readonly int $databaseFileSizeInMb;

	public readonly int $numberOfSongs;

	public function __construct(string $raw)
	{
		if (empty($raw))
		{
			// NB: An empty string is failure, DB file not found. This is expected.
			$this->databaseFileSizeInMb = $this->numberOfSongs = 0;

			return;
		}

		// NB: The "[MDB database statistics]" block (and the "File size" line in it) is only
		// printed once an on-disk database file actually exists - a freshly reset/never-populated
		// Olaf still reports "Number of songs (#): 0" etc. without it, so its absence just means
		// an empty database, not a parse failure
		$this->databaseFileSizeInMb = preg_match('/File size of the databases:\s*(\d+)MB/', $raw, $m)
			? intval($m[1])
			: 0;

		if (! preg_match('/Number of songs \(#\):\s+(\d+)/', $raw, $m))
			throw new UnexpectedValueException('Failed to find # of songs in stats');

		$this->numberOfSongs = intval($m[1]);
	}
}
