<?php

namespace App\Support\Olaf;

use UnexpectedValueException;

class Stats
{
    public readonly int $databaseFileSizeInMb;
    public readonly int $numberOfSongs;

    public function __construct(string $raw)
    {
        if(empty($raw))
        {
            // NB: An empty string is failure, DB file not found. This is expected.
            $this->databaseFileSizeInMb = $this->numberOfSongs = 0;
        }
        else
        {
            if(!preg_match('/File size of the databases:\s*(\d+)MB/', $raw, $m))
                throw new UnexpectedValueException('Failed to find database size in stats');

            $this->databaseFileSizeInMb = intval($m[1]);

            if(!preg_match('/Number of songs \(#\):\s+(\d+)/', $raw, $m))
                throw new UnexpectedValueException('Failed to find # of songs in stats');

            $this->numberOfSongs = intval($m[1]);
        }
    }
}
