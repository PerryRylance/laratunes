<?php

namespace App\Support;

use App\Support\Olaf\QueryResults;
use App\Support\Olaf\Stats;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class Olaf
{
    private Client $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => 'http://host.docker.internal:5000/'
        ]);
    }

    public function reset(): void
    {
        $this->client->request('DELETE', 'reset.php');
    }

    public function stats(): Stats
    {
        $response = $this->client->request('GET', 'stats.php');
        $body = (string)$response->getBody();

        return new Stats($body);
    }

    public function store(string $filename): void
    {
        $this->client->request('POST', 'store.php', [
            'form_params' => [
                'file' => $filename
            ]
        ]);
    }

    public function query(string $filename): QueryResults
    {
        $params = ['file' => $filename];
        $qstr = http_build_query($params);
        $body = (string)$this->client->request('GET', "query.php?$qstr")->getBody();

        return new QueryResults($filename, $body);
    }

    public function delete(string $filename): void
    {
        $params = ['file' => $filename];
        $qstr = http_build_query($params);

        $this->client->request('DELETE', "delete.php?$qstr");
    }
}
