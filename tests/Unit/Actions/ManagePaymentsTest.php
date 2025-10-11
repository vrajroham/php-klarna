<?php

namespace Vrajroham\PhpKlarna\Tests\Unit\Actions;

use Vrajroham\PhpKlarna\Resources\CustomerTokenFromAuthorization;
use Vrajroham\PhpKlarna\Resources\OrderCreated;
use Vrajroham\PhpKlarna\Resources\RespondSuccess;
use Vrajroham\PhpKlarna\Resources\Session;
use Vrajroham\PhpKlarna\Tests\TestCase;

/**
 * @covers \Vrajroham\PhpKlarna\Actions\ManagePayments
 */
class ManagePaymentsTest extends TestCase
{
    public function test_create_session_returns_session_resource(): void
    {
        $sessionData = $this->getSampleSessionData();
        $responseData = [
            'session_id' => 'session_123',
            'client_token' => 'client_token_abc',
            'payment_method_categories' => [
                ['identifier' => 'pay_later', 'name' => 'Pay later']
            ]
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createSession($sessionData);

        $this->assertInstanceOfResource(Session::class, $result);
        $this->assertEquals('session_123', $result->sessionId);
        $this->assertEquals('client_token_abc', $result->clientToken);
    }

    public function test_create_session_makes_correct_api_call(): void
    {
        $sessionData = $this->getSampleSessionData();
        $responseData = ['session_id' => 'session_123'];

        $this->mockApiResponse($responseData);

        $this->klarna->createSession($sessionData);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString('payments/v1/sessions', (string) $lastRequest->getUri());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals($sessionData['purchase_country'], $requestBody['purchase_country']);
        $this->assertEquals($sessionData['order_amount'], $requestBody['order_amount']);
    }

    public function test_create_customer_token_returns_customer_token_resource(): void
    {
        $authToken = 'auth_token_123';
        $tokenData = $this->getSampleCustomerTokenData();
        $responseData = [
            'token_id' => 'token_456',
            'status' => 'active',
            'payment_method_type' => 'invoice'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createCustomerToken($authToken, $tokenData);

        $this->assertInstanceOfResource(CustomerTokenFromAuthorization::class, $result);
        $this->assertEquals('token_456', $result->tokenId);
        $this->assertEquals('active', $result->status);
    }

    public function test_create_customer_token_makes_correct_api_call(): void
    {
        $authToken = 'auth_token_123';
        $tokenData = $this->getSampleCustomerTokenData();
        $responseData = ['token_id' => 'token_456'];

        $this->mockApiResponse($responseData);

        $this->klarna->createCustomerToken($authToken, $tokenData);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString("payments/v1/authorizations/{$authToken}/customer-token", (string) $lastRequest->getUri());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals($tokenData['purchase_country'], $requestBody['purchase_country']);
    }

    public function test_create_order_from_authorization_token_returns_order_resource(): void
    {
        $authToken = 'auth_token_123';
        $orderData = $this->getSampleOrderData();
        $responseData = [
            'order_id' => 'order_789',
            'klarna_reference' => 'klarna_ref_456',
            'fraud_status' => 'ACCEPTED',
            'authorized_payment_method' => [
                'type' => 'invoice'
            ]
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createOrderFromAuthorizationToken($authToken, $orderData);

        $this->assertInstanceOfResource(OrderCreated::class, $result);
        $this->assertEquals('order_789', $result->orderId);
        $this->assertEquals('klarna_ref_456', $result->klarnaReference);
        $this->assertEquals('ACCEPTED', $result->fraudStatus);
    }

    public function test_create_order_from_authorization_token_makes_correct_api_call(): void
    {
        $authToken = 'auth_token_123';
        $orderData = $this->getSampleOrderData();
        $responseData = ['order_id' => 'order_789'];

        $this->mockApiResponse($responseData);

        $this->klarna->createOrderFromAuthorizationToken($authToken, $orderData);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString("payments/v1/authorizations/{$authToken}/order", (string) $lastRequest->getUri());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals($orderData['purchase_country'], $requestBody['purchase_country']);
        $this->assertEquals($orderData['order_amount'], $requestBody['order_amount']);
        $this->assertEquals($orderData['merchant_reference1'], $requestBody['merchant_reference1']);
    }

    public function test_cancel_authorization_returns_success_response(): void
    {
        $authToken = 'auth_token_123';
        $data = [];
        $responseData = [
            'success' => true,
            'message' => 'Authorization cancelled successfully'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->cancelAuthorization($authToken, $data);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('Authorization cancelled successfully', $result->message);
    }

    public function test_cancel_authorization_makes_correct_api_call(): void
    {
        $authToken = 'auth_token_123';
        $data = ['reason' => 'customer_request'];
        $responseData = ['success' => true];

        $this->mockApiResponse($responseData);

        $this->klarna->cancelAuthorization($authToken, $data);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('DELETE', $lastRequest->getMethod());
        $this->assertStringContainsString("payments/v1/authorizations/{$authToken}", (string) $lastRequest->getUri());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals($data['reason'], $requestBody['reason']);
    }

    public function test_create_session_with_minimal_data(): void
    {
        $minimalData = [
            'purchase_country' => 'US',
            'purchase_currency' => 'USD',
            'locale' => 'en-US',
            'order_amount' => 5000,
            'order_lines' => [
                [
                    'name' => 'Minimal Product',
                    'quantity' => 1,
                    'unit_price' => 5000,
                    'total_amount' => 5000
                ]
            ]
        ];

        $responseData = ['session_id' => 'minimal_session'];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createSession($minimalData);

        $this->assertInstanceOfResource(Session::class, $result);
        $this->assertEquals('minimal_session', $result->sessionId);
    }

    public function test_create_session_with_complex_data(): void
    {
        $complexData = [
            'purchase_country' => 'SE',
            'purchase_currency' => 'SEK',
            'locale' => 'sv-SE',
            'order_amount' => 25000,
            'order_tax_amount' => 5000,
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'Premium Product',
                    'quantity' => 2,
                    'unit_price' => 10000,
                    'tax_rate' => 2500,
                    'total_amount' => 20000,
                    'total_tax_amount' => 4000
                ],
                [
                    'type' => 'shipping_fee',
                    'name' => 'Express Shipping',
                    'quantity' => 1,
                    'unit_price' => 5000,
                    'tax_rate' => 2500,
                    'total_amount' => 5000,
                    'total_tax_amount' => 1000
                ]
            ],
            'merchant_urls' => [
                'terms' => 'https://example.com/terms',
                'checkout' => 'https://example.com/checkout',
                'confirmation' => 'https://example.com/confirmation',
                'push' => 'https://example.com/push'
            ],
            'merchant_reference1' => 'COMPLEX-ORDER-001',
            'merchant_reference2' => 'STORE-REF-123'
        ];

        $responseData = [
            'session_id' => 'complex_session',
            'client_token' => 'complex_token'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createSession($complexData);

        $this->assertInstanceOfResource(Session::class, $result);
        $this->assertEquals('complex_session', $result->sessionId);
        $this->assertEquals('complex_token', $result->clientToken);
    }

    public function test_create_order_with_auto_capture(): void
    {
        $authToken = 'auth_token_123';
        $orderData = array_merge($this->getSampleOrderData(), [
            'auto_capture' => true
        ]);

        $responseData = [
            'order_id' => 'auto_capture_order',
            'klarna_reference' => 'auto_ref',
            'fraud_status' => 'ACCEPTED'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createOrderFromAuthorizationToken($authToken, $orderData);

        $this->assertInstanceOfResource(OrderCreated::class, $result);
        $this->assertEquals('auto_capture_order', $result->orderId);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertTrue($requestBody['auto_capture']);
    }

    public function test_create_customer_token_with_billing_address(): void
    {
        $authToken = 'auth_token_123';
        $tokenData = [
            'purchase_country' => 'GB',
            'purchase_currency' => 'GBP',
            'locale' => 'en-GB',
            'billing_address' => [
                'given_name' => 'Jane',
                'family_name' => 'Smith',
                'email' => 'jane.smith@example.com',
                'street_address' => '456 High Street',
                'postal_code' => 'SW1A 1AA',
                'city' => 'London',
                'country' => 'GB'
            ]
        ];

        $responseData = [
            'token_id' => 'gb_token_123',
            'status' => 'active'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createCustomerToken($authToken, $tokenData);

        $this->assertInstanceOfResource(CustomerTokenFromAuthorization::class, $result);
        $this->assertEquals('gb_token_123', $result->tokenId);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('Jane', $requestBody['billing_address']['given_name']);
        $this->assertEquals('SW1A 1AA', $requestBody['billing_address']['postal_code']);
    }
}