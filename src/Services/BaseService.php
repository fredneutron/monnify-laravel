<?php

namespace Monnify\MonnifyLaravel\Services;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\PendingRequest;
use Monnify\MonnifyLaravel\Enums\HttpMethod;
use Illuminate\Support\Facades\Cache;

abstract class BaseService
{
    public function __construct(protected PendingRequest $client)
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
            $request = $this->client->withToken($accessToken);

            if (!empty($parameters)) {
                $request = $request->withQueryParameters($parameters);
            }

            $response = match ($method) {
                HttpMethod::GET    => $request->get($endpoint),
                HttpMethod::POST   => $request->post($endpoint, $data),
                HttpMethod::PUT    => $request->put($endpoint, $data),
                HttpMethod::DELETE => $request->delete($endpoint, $data),
                default            => throw new Exception("Unsupported HTTP method: {$method->value}")
            };

            return [
                'status' => $response->getStatusCode(),
                'body' => $response->object(),
            ];
        } catch (RequestException $e) {
            $response = $e->response;

            return [
                'status' => $response ? $response->status() : 500,
                'error' => $response ? $response->json() : ['message' => $e->getMessage()],
            ];
        } catch (Exception $e) {
            return [
                'status' => 500,
                'error' => ['message' => $e->getMessage()],
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
            $response = $this->client->withBasicAuth(
                config('monnify.api_key'),
                config('monnify.secret_key')
            )->post('/api/v1/auth/login');

            $response = $response->object();
            $content = $response->responseBody;
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