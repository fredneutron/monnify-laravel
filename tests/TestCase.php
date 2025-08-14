<?php

namespace Monnify\MonnifyLaravel\Tests;

use Illuminate\Http\Client\PendingRequest;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Monnify\MonnifyLaravel\MonnifyServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            MonnifyServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Load environment variables from .env file
        $this->loadEnvironmentVariables();
        
        // Set up configuration values from environment variables
        $app['config']->set('monnify.api_key', env('MONNIFY_API_KEY', 'test-api-key'));
        $app['config']->set('monnify.secret_key', env('MONNIFY_SECRET_KEY', 'test-secret-key'));
        $app['config']->set('monnify.environment', env('MONNIFY_ENVIRONMENT', 'SANDBOX'));
        $app['config']->set('monnify.wallet_account_number', env('MONNIFY_WALLET_ACCOUNT_NUMBER'));
        $app['config']->set('monnify.contract_number', env('MONNIFY_CONTRACT_NUMBER'));
    }
    
    /**
     * Load environment variables from .env file
     */
    protected function loadEnvironmentVariables()
    {
        // Check if .env file exists in the package root
        $envFile = __DIR__ . '/../.env';
        
        if (file_exists($envFile)) {
            // Load environment variables from .env file
            $dotenv = \Dotenv\Dotenv::createImmutable(dirname($envFile));
            $dotenv->load();
        }
    }

    public function accessTokenSetup($mockResponse, String $http_method, string $class)
    {
        $mockRequest = $this->createMock(PendingRequest::class);
        $mockRequest->method('withToken')->willReturnSelf();
        $mockRequest->method('withQueryParameters')->willReturnSelf();
        $mockRequest->method($http_method)->willReturn($mockResponse);

        // Stub getAccessToken() to prevent hitting actual Monnify logic
        $service = $this->getMockBuilder($class)
            ->setConstructorArgs([$mockRequest])
            ->onlyMethods(['getAccessToken'])
            ->getMock();
        
        return $service;
    }

    public function responseBody(): object
    {
        return (object)[
            'requestSuccessful' => true,
            'responseMessage' => 'success'
        ];
    }

    // protected function tearDown(): void
    // {
    //     parent::tearDown();
    //     // Reset any mocks or global state here if needed
    // }
}