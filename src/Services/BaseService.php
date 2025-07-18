<?php

namespace Monnify\MonnifyLaravel\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Monnify\MonnifyLaravel\Enums\HttpMethod;
use Illuminate\Support\Facades\Cache;

abstract class BaseService
{
    public function __construct(protected Client $client)
    {
        $this->client = $client;
    }

    protected function makeRequest(
        HttpMethod $method,
        string $endpoint,
        array $data = [],
        array $parameters = []
    ): array {
        try {
            $accessToken = $this->getAccessToken();
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ]
            ];
            
            if (!empty($data)) {
                $options['json'] = $data;
            }

            if (!empty($parameters)) {
                $options['query'] = $parameters;
            }

            $response = $this->client->request($method->value, $endpoint, $options);

            return [
                'status' => $response->getStatusCode(),
                'body' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (RequestException $e) {
            return [
                'status' => (int) $e->getCode(),
                'error' => json_decode($e->getResponse()->getBody()->getContents()),
            ];
        }
    }

    public function getAccessToken(): string
    {
        $cache = Cache::store(config('monnify.cache_store', 'monnify_file'));

        if ($cache->has('monnify_access_token')) {
            return $cache->get('monnify_access_token');
        }

        try {
            $response = $this->client->post('/api/v1/auth/login', [
                'auth' => [
                    config('monnify.api_key'),
                    config('monnify.secret_key'),
                ]
            ]);

            $response = (object) json_decode($response->getBody()->getContents(), true);
            $content = (object) $response->responseBody;
            $accessToken = $content->accessToken;
            // store token
            $this->setAccessToken($accessToken, $content->expiresIn);

            return $accessToken;
        } catch (Exception $e) {
            throw new Exception(
                message: $e->getMessage(),
                code: (int) $e->getCode()
            );
        }
    }

    public function setAccessToken(
        string $accessToken,
        int $expiresIn
    ): void {
        $cache = Cache::store(config('monnify.cache_store', 'monnify_file'));
        $cache->put('monnify_access_token', $accessToken, $expiresIn);
    }
}