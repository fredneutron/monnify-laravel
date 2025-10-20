<?php

namespace Monnify\MonnifyLaravel\Tests\Feature;

use Monnify\MonnifyLaravel\Services\DisbursementService;
use Monnify\MonnifyLaravel\Tests\TestCase;

class DisbursementServiceTest extends TestCase
{
    public function test_single()
    {
        $data = [
            'amount' => 200,
            'reference' =>'reference---1290034',
            'narration' =>'911 Transaction',
            'destinationBankCode' => '057',
            'destinationAccountNumber' => '2085886873',
            'currency' => 'NGN',
            'sourceAccountNumber' => config('monnify.account_number')
        ];
        
        $service = $this->service('post', $this->responseBody());
        $result = $service->single($data);

        $this->statusResponse($result);
    }

    public function test_bulk()
    {
        $data = [
            'title'  => 'Game of Batches',
            'batchReference' => 'batchreference--12934',
            'narration' => '911 Transaction',
            'sourceAccountNumber' => config('monnify.account_number'),
            'onValidationFailure'  => 'CONTINUE',
            'notificationInterval' => 10,
            'transactionList'  => [
                [
                    'amount' => 1300,
                    'reference' => 'Final-Refere-nce-1a',
                    'narration' => '911 Transaction',
                    'destinationBankCode' => '058',
                    'destinationAccountNumber' => '0111946768',
                    'currency' => 'NGN'
                ],
                [
                    'amount' => 570,
                    'reference' => 'Final-Ref-erence-2a',
                    'narration' => '911 Transaction',
                    'destinationBankCode' => '058',
                    'destinationAccountNumber' => '0111946768',
                    'currency' => 'NGN'
                ],
                [
                    'amount' => 230,
                    'reference' => 'Final-Refer-ence-3a',
                    'narration' => '911 Transaction',
                    'destinationBankCode' => '058',
                    'destinationAccountNumber' => '0111946768',
                    'currency' => 'NGN'
                ]

            ]
        ];
        
        $service = $this->service('post', $this->responseBody());
        $result = $service->bulk($data);

        $this->statusResponse($result);
    }

    public function test_authorise_single()
    {
        $data = [
            'reference' => 'refere--n00ce---1290034',
            'authorizationCode' => '491763'
        ];

        $service = $this->service('post', $this->responseBody());
        $result = $service->authoriseSingle($data);

        $this->statusResponse($result);
    }

    public function test_authorise_bulk()
    {
        $data = [
            'reference' => 'batchre-ference--12934',
            'authorizationCode' => '122080'
        ];

        $service = $this->service('post', $this->responseBody());
        $result = $service->authoriseBulk($data);

        $this->statusResponse($result);
    }

    public function test_resend()
    {
        $reference = 'refere--n00ce---1290--034';
        $service = $this->service('post', $this->responseBody());
        $result = $service->resendOTP($reference);

        $this->statusResponse($result);
    }

    public function test_single_status()
    {
        $reference = 'refere--n00ce---1290--034';
        $service = $this->service('get', $this->responseBody());
        $result = $service->singleStatus($reference);

        $this->statusResponse($result);
    }

    public function test_bulk_status()
    {
        $batchReference = 'batchreference--12934';
        $service = $this->service('get', $this->responseBody());
        $result = $service->bulkStatus($batchReference);

        $this->statusResponse($result);
    }

    public function test_list_single()
    {
        $service = $this->service('get', $this->responseBody());
        $result = $service->all();

        $this->statusResponse($result);
    }

    public function test_list_bulk()
    {
        $service = $this->service('get', $this->responseBody());
        $result = $service->all();

        $this->statusResponse($result);
    }

    public function test_get_bulk_transaction()
    {
        $batchReference = 'batchreference--12934';
        $service = $this->service('get', $this->responseBody());
        $result = $service->bulkTransaction($batchReference);

        $this->statusResponse($result);
    }

    public function test_search()
    {
        $sourceAccountNumber = config('monnify.wallet_account_number');
        $service = $this->service('get', $this->responseBody());
        $result = $service->search($sourceAccountNumber);

        $this->statusResponse($result);
    }

    protected function service(String $method, $response)
    {
        return $this->accessTokenSetup(
            $this->mockResponse($response),
            $method,
            DisbursementService::class
        );
    }

    protected function statusResponse($result)
    {
        $this->assertEquals(200, $result['status']);
        $this->assertEquals('success', $result['body']->responseMessage);
    }
}