<?php

namespace Monnify\MonnifyLaravel\Tests\Feature;

use InvalidArgumentException;
use Monnify\MonnifyLaravel\Facades\Monnify;
use Monnify\MonnifyLaravel\Services\TransactionService;
use Monnify\MonnifyLaravel\Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    public function test_initialize_transaction_successfully()
    {
        $data = $this->baseTransactionData();

        $service = $this->accessTokenSetup(
            $this->mockResponse($this->response()),
            'post',
            TransactionService::class
        );

        $result = $service->initialise($data);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('TX123', $result['body']->responseBody->transactionReference);
    }

    public function test_initialize_transaction_with_invalid_data_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);

        $data = [
            'customerEmail' => 'incomplete@example.com' // Missing required fields
        ];

        Monnify::transactions()->initialise($data);
    }

    public function test_initialize_transaction_handles_failed_api_response()
    {
        $data = $this->baseTransactionData([
            'customerName' => 'John Doe',
            'paymentReference' => 'TXN-FAILURE',
            'redirectUrl' => 'https://example.com/fail'
        ]);

        $failedBody = (object)[
            'requestSuccessful' => false,
            'responseMessage' => 'Invalid credentials'
        ];

        $service = $this->accessTokenSetup(
            $this->mockResponse($failedBody, 401),
            'post',
            TransactionService::class
        );

        $result = $service->initialise($data);

        $this->assertEquals(401, $result['status']);
        $this->assertEquals('Invalid credentials', $result['body']->responseMessage);
    }

    public function test_initialize_transaction_with_missing_fields_in_response()
    {
        $incompleteBody = (object) [
            'requestSuccessful' => true,
            // 'responseBody' is missing
        ];

        $service = $this->accessTokenSetup(
            $this->mockResponse($incompleteBody),
            'post',
            TransactionService::class
        );

        $result = $service->initialise($this->baseTransactionData());

        $this->assertEquals(200, $result['status']);
        $this->assertFalse(property_exists($result['body'], 'transactionReference'));
    }

    public function test_initialize_transaction_handles_server_error()
    {
        $errorBody = (object) [
            'requestSuccessful' => false,
            'responseMessage' => 'Internal Server Error',
        ];

        $service = $this->accessTokenSetup(
            $this->mockResponse($errorBody, 500),
            'post',
            TransactionService::class
        );

        $result = $service->initialise($this->baseTransactionData());

        $this->assertEquals(500, $result['status']);
        $this->assertEquals('Internal Server Error', $result['body']->responseMessage);
    }

    public function test_pay_with_bank_transfer()
    {
        $data = [
            'transactionReference' => 'MNFY|97|20250401105818|001227',
            'bankCode' => '058'
        ];

        $service = $this->accessTokenSetup(
            $this->mockResponse($this->response()),
            'post',
            TransactionService::class
        );

        $result = $service->payWithBankTransfer($data);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('TX123', $result['body']->responseBody->transactionReference);
    }

    public function test_charge_card()
    {
        $data = [
            'transactionReference' => 'TX123',
            'collectionChannel' => 'API_NOTIFICATION',
            'card' => [
                'number' => '4111111111111111',
                'expiryMonth' => '10',
                'expiryYear' => '2022',
                'pin' => '1234',
                'cvv' => '123'
            ]
        ];

        $service = $this->accessTokenSetup(
            $this->mockResponse($this->response()),
            'post',
            TransactionService::class
        );

        $result = $service->chargeCard($data);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('TX123', $result['body']->responseBody->transactionReference);
    }

    public function test_authorise_OTP()
    {
        $data = [
            'transactionReference' => 'TX123',
            'collectionChannel' => 'API_NOTIFICATION',
            'tokenId' => '100.00-b66bef0aa8e660863c4e1177a08fefba',
            'token' => '123456'
        ];

        $service = $this->accessTokenSetup(
            $this->mockResponse($this->response()),
            'post',
            TransactionService::class
        );

        $result = $service->authorizeOTP($data);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('TX123', $result['body']->responseBody->transactionReference);
    }

    public function test_authorise_3ds_card()
    {
        $data = [
            'transactionReference' => 'TX123',
            'apiKey' => config('monnify.api_key'),
            'collectionChannel' => 'API_NOTIFICATION',
            'card' => [
                'number' => '4000000000000002',
                'expiryMonth' => '12',
                'expiryYear' => '2022',
                'pin' => '1234',
                'cvv' => '123'
            ]
        ];

        $service = $this->accessTokenSetup(
            $this->mockResponse($this->response()),
            'post',
            TransactionService::class
        );

        $result = $service->authorizeThreeDSCard($data);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('TX123', $result['body']->responseBody->transactionReference);
    }

    public function test_get_all()
    {
        $mockedBody = $this->responseBody();
        $mockedBody->responseBody = [
                (object)[
                    'transactionReference' => 'TX123',
                    'paymentLink' => 'https://paylink.example.com'
                ]
            ];
    
        $service = $this->accessTokenSetup(
            $this->mockResponse($mockedBody),
            'get',
            TransactionService::class
        );

        $result = $service->all();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('TX123', $result['body']->responseBody[0]->transactionReference);
    }

    public function test_get_status()
    {
        $service = $this->accessTokenSetup(
            $this->mockResponse($this->response()),
            'get',
            TransactionService::class
        );

        $result = $service->status('TX123');

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('TX123', $result['body']->responseBody->transactionReference);
    }

    public function test_get_status_by_reference()
    {
        $service = $this->accessTokenSetup(
            $this->mockResponse($this->response()),
            'get',
            TransactionService::class
        );

        $result = $service->statusByReference('TX123');

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('TX123', $result['body']->responseBody->transactionReference);
    }

    private function response(): object
    {
        $response = $this->responseBody();
        $response->responseBody = (object) [
            'transactionReference' => 'TX123',
            'paymentLink' => 'https://paylink.example.com'
        ];

        return $response;
    }

    // Base transaction data with override support
    private function baseTransactionData(array $overrides = []): array
    {
        return array_merge([
            'amount' => 1000,
            'customerName' => 'Jane Doe',
            'customerEmail' => 'jane@example.com',
            'paymentReference' => 'TEST12345',
            'paymentDescription' => 'Test payment',
            'currencyCode' => 'NGN',
            'contractCode' => config('monnify.contract_code'),
            'redirectUrl' => 'https://example.com/redirect',
            'paymentMethods' => ['CARD', 'ACCOUNT_TRANSFER'],
        ], $overrides);
    }
}
