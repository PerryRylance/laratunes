<?php

namespace App\Services;

use App\Contracts\OlafContract;
use App\Support\Olaf\QueryResults;
use App\Support\Olaf\Stats;
use GuzzleHttp\Client;

class OlafService implements OlafContract
{
    private static ?Client $client = null;

    private static function maybeInitClient(): void
    {
        if(static::$client)
            return;

        static::$client = new \GuzzleHttp\Client([
            'base_uri' => 'http://host.docker.internal:5000/'
        ]);
    }

    public static function reset(): void
    {
        static::maybeInitClient();

        static::$client->request('DELETE', 'reset.php');
    }

    public static function stats(): Stats
    {
        static::maybeInitClient();

        $response = static::$client->request('GET', 'stats.php');
        $body = (string)$response->getBody();

        return new Stats($body);
    }

    public static function store(string $filename): void
    {
        static::maybeInitClient();

        static::$client->request('POST', 'store.php', [
            'form_params' => [
                'file' => $filename
            ]
        ]);
    }

    public static function query(string $filename): QueryResults
    {
        static::maybeInitClient();

        $params = ['file' => $filename];
        $qstr = http_build_query($params);
        $body = (string)static::$client->request('GET', "query.php?$qstr")->getBody();

        return new QueryResults($filename, $body);
    }

    public static function delete(string $filename): void
    {
        static::maybeInitClient();

        $params = ['file' => $filename];
        $qstr = http_build_query($params);

        static::$client->request('DELETE', "delete.php?$qstr");
    }
}
