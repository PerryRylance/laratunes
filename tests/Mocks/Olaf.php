<?php

namespace Tests\Mocks;

use App\Contracts\OlafContract;
use App\Support\Olaf\QueryResults;
use App\Support\Olaf\Stats;

class Olaf implements OlafContract
{
	public static function reset(): void {}

	public static function stats(): Stats
	{
		return new Stats('');
	}

	public static function fingerprint(string $filename): void {}

	public static function query(string $filename): QueryResults
	{
		$result = new QueryResults($filename, '');

		$result->items->push([
			'confidence' => 123,
			'file' => 'fake.mp3',
		]);

		return $result;
	}

	public static function delete(string $filename): void {}
}
