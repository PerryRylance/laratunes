<?php

namespace App\Support\Olaf;

use League\Csv\Reader;
use Illuminate\Support\Collection;

class QueryResults extends Collection
{
    public function __construct(string $filename, string $raw)
    {
        $results = [];

        $reader = Reader::fromString($raw);
        $records = $reader->getRecords();

        foreach($records as $record)
        {
            $record = array_map('trim', $record);

            if($record[2] !== $filename)
                continue;

            if($record[4] === 'match count (#)')
                continue;

            if(empty($record[7]))
                continue;

            $confidence = intval($record[4]);

            if($confidence === 0) // TODO: Or some threshold?
                continue;

            $results []= (object)[
                'confidence' => $confidence,
                'file' => preg_replace('/^\/root\/audio\//', '', $record[7])
            ];
        }

        $items = (new Collection($results))->sortByDesc('confidence')->values()->toArray();

        parent::__construct($items);
    }
}
