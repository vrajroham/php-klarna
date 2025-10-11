<?php

namespace Vrajroham\PhpKlarna\Tests\Unit\Actions;

use Vrajroham\PhpKlarna\Resources\CustomerToken;
use Vrajroham\PhpKlarna\Resources\Order;
use Vrajroham\PhpKlarna\Tests\TestCase;

/**
 * @covers \Vrajroham\PhpKlarna\Actions\ManageCustomerTokens
 */
class ManageCustomerTokensTest extends TestCase
{
    public function test_customer_token_returns_customer_token_resource(): void
    {
        $tokenId = 'token_123';
        $responseData = [
            'token_id' => $tokenId,
            'status' => 'active',
            'payment_method_type' => 'invoice',
            'masked_number' => '****1234',
            'brand' => 'Klarna',
            'expires_at' => '2024-12-31T23:59:59Z'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->customerToken($tokenId);

        $this->assertInstanceOfResource(CustomerToken::class, $result);
        $this->assertEquals($tokenId, $result->tokenId);
        $this->assertEquals('active', $result->status);
        $this->assertEquals('invoice', $result->paymentMethodType);
        $this->assertEquals('****1234', $result->maskedNumber);
        $this->assertEquals('Klarna', $result->brand);
    }

    public function test_customer_token_makes_correct_api_call(): void
    {
        $tokenId = 'token_123';
        $responseData = ['token_id' => $tokenId];

        $this->mockApiResponse($responseData);

        $this->klarna->customerToken($tokenId);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('GET', $lastRequest->getMethod());
        $this->assertStringContainsString("customer-token/v1/tokens/{$tokenId}", (string) $lastRequest->getUri());
    }

    public function test_create_order_from_customer_token_returns_order_resource(): void
    {
        $tokenId = 'token_456';
        $orderData = $this->getSampleOrderData();
        $responseData = [
            'order_id' => 'order_from_token_789',
            'klarna_reference' => 'klarna_ref_abc',
            'status' => 'AUTHORIZED',
            'fraud_status' => 'ACCEPTED',
            'authorized_payment_method' => [
                'type' => 'invoice',
                'description' => 'Invoice'
            ],
            'purchase_country' => 'US',
            'purchase_currency' => 'USD',
            'locale' => 'en-US',
            'order_amount' => 10000,
            'captured_amount' => 0,
            'refunded_amount' => 0,
            'remaining_authorized_amount' => 10000
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createOrderFromCustomerToken($tokenId, $orderData);

        $this->assertInstanceOfResource(Order::class, $result);
        $this->assertEquals('order_from_token_789', $result->orderId);
        $this->assertEquals('klarna_ref_abc', $result->klarnaReference);
        $this->assertEquals('AUTHORIZED', $result->status);
        $this->assertEquals('ACCEPTED', $result->fraudStatus);
        $this->assertEquals(10000, $result->orderAmount);
    }

    public function test_create_order_from_customer_token_makes_correct_api_call(): void
    {
        $tokenId = 'token_456';
        $orderData = $this->getSampleOrderData();
        $responseData = ['order_id' => 'order_from_token_789'];

        $this->mockApiResponse($responseData);

        $this->klarna->createOrderFromCustomerToken($tokenId, $orderData);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString("customer-token/v1/tokens/{$tokenId}/order", (string) $lastRequest->getUri());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals($orderData['purchase_country'], $requestBody['purchase_country']);
        $this->assertEquals($orderData['order_amount'], $requestBody['order_amount']);
        $this->assertEquals($orderData['merchant_reference1'], $requestBody['merchant_reference1']);
    }

    public function test_customer_token_with_card_payment_method(): void
    {
        $tokenId = 'card_token_123';
        $responseData = [
            'token_id' => $tokenId,
            'status' => 'active',
            'payment_method_type' => 'card',
            'masked_number' => '****4567',
            'brand' => 'Visa',
            'expires_at' => '2025-06-30T23:59:59Z',
            'last_four' => '4567',
            'card_type' => 'credit'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->customerToken($tokenId);

        $this->assertInstanceOfResource(CustomerToken::class, $result);
        $this->assertEquals($tokenId, $result->tokenId);
        $this->assertEquals('card', $result->paymentMethodType);
        $this->assertEquals('Visa', $result->brand);
        $this->assertEquals('4567', $result->lastFour);
        $this->assertEquals('credit', $result->cardType);
    }

    public function test_customer_token_expired_status(): void
    {
        $tokenId = 'expired_token_456';
        $responseData = [
            'token_id' => $tokenId,
            'status' => 'expired',
            'payment_method_type' => 'invoice',
            'masked_number' => '****9876',
            'brand' => 'Klarna',
            'expires_at' => '2023-01-01T00:00:00Z'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->customerToken($tokenId);

        $this->assertInstanceOfResource(CustomerToken::class, $result);
        $this->assertEquals($tokenId, $result->tokenId);
        $this->assertEquals('expired', $result->status);
        $this->assertEquals('2023-01-01T00:00:00Z', $result->expiresAt);
    }

    public function test_create_order_from_customer_token_with_auto_capture(): void
    {
        $tokenId = 'token_auto_capture';
        $orderData = array_merge($this->getSampleOrderData(), [
            'auto_capture' => true
        ]);

        $responseData = [
            'order_id' => 'auto_capture_order_123',
            'klarna_reference' => 'auto_ref_456',
            'status' => 'CAPTURED',
            'fraud_status' => 'ACCEPTED',
            'order_amount' => 10000,
            'captured_amount' => 10000,
            'refunded_amount' => 0,
            'remaining_authorized_amount' => 0
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createOrderFromCustomerToken($tokenId, $orderData);

        $this->assertInstanceOfResource(Order::class, $result);
        $this->assertEquals('auto_capture_order_123', $result->orderId);
        $this->assertEquals('CAPTURED', $result->status);
        $this->assertEquals(10000, $result->capturedAmount);
        $this->assertEquals(0, $result->remainingAuthorizedAmount);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertTrue($requestBody['auto_capture']);
    }

    public function test_create_order_from_customer_token_with_shipping_info(): void
    {
        $tokenId = 'token_shipping_789';
        $orderData = array_merge($this->getSampleOrderData(), [
            'shipping_address' => [
                'given_name' => 'John',
                'family_name' => 'Doe',
                'street_address' => '123 Shipping St',
                'postal_code' => '12345',
                'city' => 'Shipping City',
                'region' => 'CA',
                'country' => 'US'
            ]
        ]);

        $responseData = [
            'order_id' => 'shipping_order_456',
            'klarna_reference' => 'shipping_ref_789',
            'status' => 'AUTHORIZED',
            'fraud_status' => 'ACCEPTED'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createOrderFromCustomerToken($tokenId, $orderData);

        $this->assertInstanceOfResource(Order::class, $result);
        $this->assertEquals('shipping_order_456', $result->orderId);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('John', $requestBody['shipping_address']['given_name']);
        $this->assertEquals('123 Shipping St', $requestBody['shipping_address']['street_address']);
        $this->assertEquals('US', $requestBody['shipping_address']['country']);
    }

    public function test_create_order_from_customer_token_with_complex_order_lines(): void
    {
        $tokenId = 'token_complex_123';
        $orderData = [
            'purchase_country' => 'SE',
            'purchase_currency' => 'SEK',
            'locale' => 'sv-SE',
            'order_amount' => 75000,
            'order_tax_amount' => 15000,
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'Premium Laptop',
                    'quantity' => 1,
                    'unit_price' => 50000,
                    'tax_rate' => 2500,
                    'total_amount' => 50000,
                    'total_tax_amount' => 10000
                ],
                [
                    'type' => 'physical',
                    'name' => 'Laptop Case',
                    'quantity' => 1,
                    'unit_price' => 15000,
                    'tax_rate' => 2500,
                    'total_amount' => 15000,
                    'total_tax_amount' => 3000
                ],
                [
                    'type' => 'shipping_fee',
                    'name' => 'Express Delivery',
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'tax_rate' => 2500,
                    'total_amount' => 10000,
                    'total_tax_amount' => 2000
                ]
            ],
            'merchant_reference1' => 'RECURRING-ORDER-456',
            'merchant_reference2' => 'CUSTOMER-TOKEN-ORDER'
        ];

        $responseData = [
            'order_id' => 'complex_token_order_789',
            'klarna_reference' => 'complex_ref_abc',
            'status' => 'AUTHORIZED',
            'fraud_status' => 'ACCEPTED',
            'order_amount' => 75000,
            'order_tax_amount' => 15000
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createOrderFromCustomerToken($tokenId, $orderData);

        $this->assertInstanceOfResource(Order::class, $result);
        $this->assertEquals('complex_token_order_789', $result->orderId);
        $this->assertEquals(75000, $result->orderAmount);
        $this->assertEquals(15000, $result->orderTaxAmount);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertCount(3, $requestBody['order_lines']);
        $this->assertEquals('Premium Laptop', $requestBody['order_lines'][0]['name']);
        $this->assertEquals('Express Delivery', $requestBody['order_lines'][2]['name']);
        $this->assertEquals('RECURRING-ORDER-456', $requestBody['merchant_reference1']);
    }

    public function test_customer_token_with_minimal_response(): void
    {
        $tokenId = 'minimal_token_789';
        $responseData = [
            'token_id' => $tokenId,
            'status' => 'active'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->customerToken($tokenId);

        $this->assertInstanceOfResource(CustomerToken::class, $result);
        $this->assertEquals($tokenId, $result->tokenId);
        $this->assertEquals('active', $result->status);
    }

    public function test_create_order_from_customer_token_different_currencies(): void
    {
        $tokenId = 'currency_token_456';
        
        // Test with GBP
        $gbpOrderData = [
            'purchase_country' => 'GB',
            'purchase_currency' => 'GBP',
            'locale' => 'en-GB',
            'order_amount' => 8500, // £85.00
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'British Product',
                    'quantity' => 1,
                    'unit_price' => 8500,
                    'total_amount' => 8500
                ]
            ],
            'merchant_reference1' => 'GBP-ORDER-123'
        ];

        $responseData = [
            'order_id' => 'gbp_order_789',
            'purchase_currency' => 'GBP',
            'order_amount' => 8500,
            'status' => 'AUTHORIZED'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createOrderFromCustomerToken($tokenId, $gbpOrderData);

        $this->assertInstanceOfResource(Order::class, $result);
        $this->assertEquals('gbp_order_789', $result->orderId);
        $this->assertEquals('GBP', $result->purchaseCurrency);
        $this->assertEquals(8500, $result->orderAmount);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('GB', $requestBody['purchase_country']);
        $this->assertEquals('GBP', $requestBody['purchase_currency']);
        $this->assertEquals('en-GB', $requestBody['locale']);
    }

    public function test_customer_token_different_payment_method_types(): void
    {
        $testCases = [
            [
                'token_id' => 'direct_debit_token',
                'payment_method_type' => 'direct_debit',
                'brand' => 'Klarna',
                'masked_number' => '****5678'
            ],
            [
                'token_id' => 'paylater_token',
                'payment_method_type' => 'paylater',
                'brand' => 'Klarna',
                'masked_number' => null
            ],
            [
                'token_id' => 'paynow_token',
                'payment_method_type' => 'paynow',
                'brand' => 'Klarna',
                'masked_number' => null
            ]
        ];

        foreach ($testCases as $testCase) {
            $responseData = array_merge(['status' => 'active'], $testCase);
            
            $this->mockApiResponse($responseData);
            
            $result = $this->klarna->customerToken($testCase['token_id']);
            
            $this->assertInstanceOfResource(CustomerToken::class, $result);
            $this->assertEquals($testCase['token_id'], $result->tokenId);
            $this->assertEquals($testCase['payment_method_type'], $result->paymentMethodType);
            $this->assertEquals($testCase['brand'], $result->brand);
        }
    }
}