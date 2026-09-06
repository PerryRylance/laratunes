<?php

namespace App\Support\Olaf;

use Illuminate\Support\Collection;
use League\Csv\Reader;

class QueryResults
{
	public readonly Collection $items;

	public function __construct(string $filename, string $raw)
	{
		$results = [];

		$reader = Reader::fromString($raw);
		$records = $reader->getRecords();

		foreach ($records as $record)
		{
			$record = array_map('trim', $record);

			// NB: olaf reports query_path as the absolute container path (eg. /root/audio/query.mp3),
			// so it has to be normalised the same way as the match path below before comparing it
			// against the relative $filename the caller queried with
			$queryPath = preg_replace('/^\/root\/audio\//', '', $record[2]);

			if ($queryPath !== $filename)
			continue;

			if (empty($record[7]))
			continue;

			$confidence = intval($record[4]);

			if ($confidence === 0) // TODO: Or some threshold?
			continue;

			$results[] = [
				'confidence' => $confidence,
				'file' => preg_replace('/^\/root\/audio\//', '', $record[7]),
			];
		}

		$this->items = (new Collection($results))->sortByDesc('confidence')->values();
	}
}
