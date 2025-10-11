<?php

namespace Vrajroham\PhpKlarna\Tests\Unit\Actions;

use Vrajroham\PhpKlarna\Resources\Order;
use Vrajroham\PhpKlarna\Resources\RespondSuccess;
use Vrajroham\PhpKlarna\Tests\TestCase;

/**
 * @covers \Vrajroham\PhpKlarna\Actions\ManageOrders
 */
class ManageOrdersTest extends TestCase
{
    public function test_order_returns_order_resource(): void
    {
        $orderId = 'order_123';
        $responseData = [
            'order_id' => $orderId,
            'status' => 'AUTHORIZED',
            'purchase_country' => 'US',
            'purchase_currency' => 'USD',
            'locale' => 'en-US',
            'order_amount' => 10000,
            'captured_amount' => 5000,
            'refunded_amount' => 0,
            'remaining_authorized_amount' => 5000
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->order($orderId);

        $this->assertInstanceOfResource(Order::class, $result);
        $this->assertEquals($orderId, $result->orderId);
        $this->assertEquals('AUTHORIZED', $result->status);
        $this->assertEquals(10000, $result->orderAmount);
        $this->assertEquals(5000, $result->capturedAmount);
    }

    public function test_order_makes_correct_api_call(): void
    {
        $orderId = 'order_123';
        $responseData = ['order_id' => $orderId];

        $this->mockApiResponse($responseData);

        $this->klarna->order($orderId);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('GET', $lastRequest->getMethod());
        $this->assertStringContainsString("ordermanagement/v1/orders/{$orderId}", (string) $lastRequest->getUri());
    }

    public function test_create_capture_returns_success_response(): void
    {
        $orderId = 'order_123';
        $captureData = [
            'captured_amount' => 5000,
            'description' => 'Partial capture for shipped items',
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'Product 1',
                    'quantity' => 1,
                    'unit_price' => 5000,
                    'total_amount' => 5000
                ]
            ]
        ];

        $responseData = [
            'capture_id' => 'capture_456',
            'klarna_reference' => 'capture_ref_789'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createCapture($orderId, $captureData);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals('capture_456', $result->captureId);
        $this->assertEquals('capture_ref_789', $result->klarnaReference);
    }

    public function test_create_capture_makes_correct_api_call(): void
    {
        $orderId = 'order_123';
        $captureData = [
            'captured_amount' => 5000,
            'description' => 'Test capture'
        ];

        $this->mockApiResponse(['capture_id' => 'capture_456']);

        $this->klarna->createCapture($orderId, $captureData);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString("ordermanagement/v1/orders/{$orderId}/captures", (string) $lastRequest->getUri());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals(5000, $requestBody['captured_amount']);
        $this->assertEquals('Test capture', $requestBody['description']);
    }

    public function test_create_refund_returns_success_response(): void
    {
        $orderId = 'order_123';
        $refundData = [
            'refunded_amount' => 2500,
            'description' => 'Product return refund',
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'Returned Product',
                    'quantity' => 1,
                    'unit_price' => 2500,
                    'total_amount' => 2500
                ]
            ]
        ];

        $responseData = [
            'refund_id' => 'refund_789',
            'klarna_reference' => 'refund_ref_abc'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createRefund($orderId, $refundData);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals('refund_789', $result->refundId);
        $this->assertEquals('refund_ref_abc', $result->klarnaReference);
    }

    public function test_create_refund_makes_correct_api_call(): void
    {
        $orderId = 'order_123';
        $refundData = [
            'refunded_amount' => 2500,
            'description' => 'Test refund'
        ];

        $this->mockApiResponse(['refund_id' => 'refund_789']);

        $this->klarna->createRefund($orderId, $refundData);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString("ordermanagement/v1/orders/{$orderId}/refunds", (string) $lastRequest->getUri());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals(2500, $requestBody['refunded_amount']);
        $this->assertEquals('Test refund', $requestBody['description']);
    }

    public function test_acknowledge_order_returns_success_response(): void
    {
        $orderId = 'order_123';
        $responseData = ['acknowledged' => true];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->acknowledgeOrder($orderId);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertTrue($result->acknowledged);
    }

    public function test_acknowledge_order_makes_correct_api_call(): void
    {
        $orderId = 'order_123';
        $this->mockApiResponse(['acknowledged' => true]);

        $this->klarna->acknowledgeOrder($orderId);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString("ordermanagement/v1/orders/{$orderId}/acknowledge", (string) $lastRequest->getUri());
    }

    public function test_cancel_order_returns_success_response(): void
    {
        $orderId = 'order_123';
        $responseData = ['cancelled' => true];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->cancelOrder($orderId);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertTrue($result->cancelled);
    }

    public function test_cancel_order_makes_correct_api_call(): void
    {
        $orderId = 'order_123';
        $this->mockApiResponse(['cancelled' => true]);

        $this->klarna->cancelOrder($orderId);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString("ordermanagement/v1/orders/{$orderId}/cancel", (string) $lastRequest->getUri());
    }

    public function test_extend_authorization_time_returns_success_response(): void
    {
        $orderId = 'order_123';
        $responseData = ['extended' => true];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->extendAuthorizationTime($orderId);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertTrue($result->extended);
    }

    public function test_extend_authorization_time_makes_correct_api_call(): void
    {
        $orderId = 'order_123';
        $this->mockApiResponse(['extended' => true]);

        $this->klarna->extendAuthorizationTime($orderId);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString("ordermanagement/v1/orders/{$orderId}/extend-authorization-time", (string) $lastRequest->getUri());
    }

    public function test_update_merchant_references_returns_success_response(): void
    {
        $orderId = 'order_123';
        $data = [
            'merchant_reference1' => 'NEW-REF-001',
            'merchant_reference2' => 'UPDATED-REF-002'
        ];

        $responseData = ['updated' => true];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->updateMerchantReferences($orderId, $data);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertTrue($result->updated);
    }

    public function test_update_merchant_references_makes_correct_api_call(): void
    {
        $orderId = 'order_123';
        $data = [
            'merchant_reference1' => 'NEW-REF-001',
            'merchant_reference2' => 'UPDATED-REF-002'
        ];

        $this->mockApiResponse(['updated' => true]);

        $this->klarna->updateMerchantReferences($orderId, $data);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('PATCH', $lastRequest->getMethod());
        $this->assertStringContainsString("ordermanagement/v1/orders/{$orderId}/merchant-references", (string) $lastRequest->getUri());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('NEW-REF-001', $requestBody['merchant_reference1']);
        $this->assertEquals('UPDATED-REF-002', $requestBody['merchant_reference2']);
    }

    public function test_update_customer_update_returns_success_response(): void
    {
        $orderId = 'order_123';
        $data = [
            'billing_address' => [
                'given_name' => 'John',
                'family_name' => 'Doe',
                'email' => 'john.doe.updated@example.com',
                'street_address' => '456 Updated St',
                'postal_code' => '54321',
                'city' => 'Updated City',
                'region' => 'NY',
                'country' => 'US'
            ]
        ];

        $responseData = ['customer_updated' => true];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->updateCustomerUpdate($orderId, $data);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertTrue($result->customerUpdated);
    }

    public function test_update_customer_update_makes_correct_api_call(): void
    {
        $orderId = 'order_123';
        $data = [
            'billing_address' => [
                'given_name' => 'Jane',
                'family_name' => 'Smith',
                'email' => 'jane.smith@example.com'
            ]
        ];

        $this->mockApiResponse(['customer_updated' => true]);

        $this->klarna->updateCustomerUpdate($orderId, $data);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('PATCH', $lastRequest->getMethod());
        $this->assertStringContainsString("ordermanagement/v1/orders/{$orderId}/customer-details", (string) $lastRequest->getUri());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('Jane', $requestBody['billing_address']['given_name']);
        $this->assertEquals('jane.smith@example.com', $requestBody['billing_address']['email']);
    }

    public function test_release_remaining_authorization_returns_success_response(): void
    {
        $orderId = 'order_123';
        $responseData = ['released' => true];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->releaseRemainingAuthorization($orderId);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertTrue($result->released);
    }

    public function test_release_remaining_authorization_makes_correct_api_call(): void
    {
        $orderId = 'order_123';
        $this->mockApiResponse(['released' => true]);

        $this->klarna->releaseRemainingAuthorization($orderId);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString("ordermanagement/v1/orders/{$orderId}/release-remaining-authorization", (string) $lastRequest->getUri());
    }

    public function test_create_capture_with_full_order_amount(): void
    {
        $orderId = 'order_123';
        $captureData = [
            'captured_amount' => 10000,
            'description' => 'Full capture',
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'Complete Product Set',
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'total_amount' => 10000
                ]
            ]
        ];

        $responseData = [
            'capture_id' => 'full_capture_123',
            'captured_amount' => 10000
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createCapture($orderId, $captureData);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals('full_capture_123', $result->captureId);
        $this->assertEquals(10000, $result->capturedAmount);
    }

    public function test_create_partial_refund(): void
    {
        $orderId = 'order_123';
        $refundData = [
            'refunded_amount' => 1500,
            'description' => 'Partial refund for damaged item',
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'Damaged Item',
                    'quantity' => 1,
                    'unit_price' => 1500,
                    'total_amount' => 1500
                ]
            ]
        ];

        $responseData = [
            'refund_id' => 'partial_refund_456',
            'refunded_amount' => 1500
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createRefund($orderId, $refundData);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals('partial_refund_456', $result->refundId);
        $this->assertEquals(1500, $result->refundedAmount);
    }

    public function test_order_with_complex_structure(): void
    {
        $orderId = 'complex_order_789';
        $responseData = [
            'order_id' => $orderId,
            'status' => 'PART_CAPTURED',
            'purchase_country' => 'SE',
            'purchase_currency' => 'SEK',
            'locale' => 'sv-SE',
            'order_amount' => 50000,
            'captured_amount' => 30000,
            'refunded_amount' => 5000,
            'remaining_authorized_amount' => 20000,
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'Premium Product',
                    'quantity' => 2,
                    'unit_price' => 20000,
                    'total_amount' => 40000
                ],
                [
                    'type' => 'shipping_fee',
                    'name' => 'Express Shipping',
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'total_amount' => 10000
                ]
            ],
            'billing_address' => [
                'given_name' => 'Erik',
                'family_name' => 'Andersson',
                'email' => 'erik.andersson@example.com',
                'country' => 'SE'
            ]
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->order($orderId);

        $this->assertInstanceOfResource(Order::class, $result);
        $this->assertEquals($orderId, $result->orderId);
        $this->assertEquals('PART_CAPTURED', $result->status);
        $this->assertEquals(50000, $result->orderAmount);
        $this->assertEquals(30000, $result->capturedAmount);
        $this->assertEquals(5000, $result->refundedAmount);
        $this->assertEquals(20000, $result->remainingAuthorizedAmount);
    }
}