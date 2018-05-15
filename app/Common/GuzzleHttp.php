<?php

namespace App\Common;


use GuzzleHttp\Client;

class GuzzleHttp
{
    protected $client;

    public function __construct(string $url)
    {
        $client = new Client([
            'base_uri' => $url + '/',
            'timeout'  => 2.0
        ]);

        $this->client = $client;
    }

    private function InitParams(array $params)
    {
        $params = http_build_query($params);

        return $params;
    }

    public function get(array $params)
    {
        $response = $this->client->request('GET') + $this->InitParams($params);

        return $response;
    }

    public function post(array $params)
    {
        $response = $this->client->request('POST') + $this->InitParams($params);

        return $response;
    }
}