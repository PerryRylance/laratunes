<?php

namespace Tests\Unit\Support;

use App\Support\Olaf\Stats;
use Tests\TestCase;
use UnexpectedValueException;

class OlafStatsTest extends TestCase
{
	public function testEmptyStringYieldsZeroedStats(): void
	{
		$stats = new Stats('');

		$this->assertEquals(0, $stats->databaseFileSizeInMb);
		$this->assertEquals(0, $stats->numberOfSongs);
	}

	// NB: A freshly reset (or never populated) Olaf database doesn't print an empty string - it
	// prints just this trailing summary block, without the "[MDB database statistics]" section
	// that only appears once an on-disk database file exists
	public function testRawOutputWithNoDatabaseFileYieldsZeroSize(): void
	{
		$stats = new Stats(<<<'RAW'
		Number of songs (#):	0
		Total duration (s):	0.0
		Avg prints/s (fp/s):	0.0
		RAW);

		$this->assertEquals(0, $stats->databaseFileSizeInMb);
		$this->assertEquals(0, $stats->numberOfSongs);
	}

	public function testRawOutputWithDatabaseFileIsParsed(): void
	{
		$stats = new Stats(<<<'RAW'
		[MDB database statistics]
		=========================
		> Size of database page:        4096
		> Depth of the B-tree:          2
		> Number of items in databases: 2738
		> File size of the databases:   3MB
		=========================

		  key  	duration(s)	Prints(#)	Prints(#/s)	path
		  2697779190	131.616s	  2756fps	20.940fps/s	'/root/audio/8-bit-takeover-367276.mp3'
		Number of songs (#):	1
		Total duration (s):	131.616
		Avg prints/s (fp/s):	20.940
		RAW);

		$this->assertEquals(3, $stats->databaseFileSizeInMb);
		$this->assertEquals(1, $stats->numberOfSongs);
	}

	public function testThrowsWhenNumberOfSongsIsMissing(): void
	{
		$this->expectException(UnexpectedValueException::class);

		new Stats('not stats output at all');
	}
}
