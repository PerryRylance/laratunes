<?php

namespace App\Contracts;

use App\Support\Olaf\QueryResults;
use App\Support\Olaf\Stats;

interface OlafContract
{
    public static function reset(): void;
    public static function stats(): Stats;
    public static function fingerprint(string $filename): void;
    public static function query(string $filename): QueryResults;
    public static function delete(string $filename): void;
}
