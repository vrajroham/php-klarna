<?php

namespace Vrajroham\PhpKlarna\Tests\Feature;

use Vrajroham\PhpKlarna\Exceptions\FailedActionException;
use Vrajroham\PhpKlarna\Exceptions\NotFoundException;
use Vrajroham\PhpKlarna\Exceptions\ValidationException;
use Vrajroham\PhpKlarna\Resources\CustomerToken;
use Vrajroham\PhpKlarna\Resources\CustomerTokenFromAuthorization;
use Vrajroham\PhpKlarna\Resources\Order;
use Vrajroham\PhpKlarna\Resources\OrderCreated;
use Vrajroham\PhpKlarna\Resources\RespondSuccess;
use Vrajroham\PhpKlarna\Resources\Session;
use Vrajroham\PhpKlarna\Tests\TestCase;

/**
 * Feature tests that test complete workflows and integration scenarios
 */
class KlarnaIntegrationTest extends TestCase
{
    public function test_complete_payment_flow_with_session_and_order_creation(): void
    {
        // Step 1: Create payment session
        $sessionData = $this->getSampleSessionData();
        $sessionResponse = [
            'session_id' => 'session_abc123',
            'client_token' => 'client_token_xyz789',
            'payment_method_categories' => [
                ['identifier' => 'pay_later', 'name' => 'Pay later'],
                ['identifier' => 'pay_now', 'name' => 'Pay now']
            ]
        ];
        $this->mockApiResponse($sessionResponse);

        $session = $this->klarna->createSession($sessionData);

        $this->assertInstanceOfResource(Session::class, $session);
        $this->assertEquals('session_abc123', $session->sessionId);

        // Step 2: Create order from authorization token
        $orderData = $this->getSampleOrderData();
        $orderResponse = [
            'order_id' => 'order_def456',
            'klarna_reference' => 'klarna_ref_ghi789',
            'fraud_status' => 'ACCEPTED',
            'authorized_payment_method' => ['type' => 'invoice']
        ];
        $this->mockApiResponse($orderResponse);

        $order = $this->klarna->createOrderFromAuthorizationToken('auth_token_123', $orderData);

        $this->assertInstanceOfResource(OrderCreated::class, $order);
        $this->assertEquals('order_def456', $order->orderId);
        $this->assertEquals('ACCEPTED', $order->fraudStatus);
    }

    public function test_complete_order_management_workflow(): void
    {
        $orderId = 'order_workflow_123';

        // Step 1: Get order details
        $orderResponse = [
            'order_id' => $orderId,
            'status' => 'AUTHORIZED',
            'order_amount' => 20000,
            'captured_amount' => 0,
            'refunded_amount' => 0,
            'remaining_authorized_amount' => 20000
        ];
        $this->mockApiResponse($orderResponse);

        $order = $this->klarna->order($orderId);
        $this->assertInstanceOfResource(Order::class, $order);
        $this->assertEquals('AUTHORIZED', $order->status);

        // Step 2: Acknowledge order
        $this->mockApiResponse(['acknowledged' => true]);
        $acknowledgeResult = $this->klarna->acknowledgeOrder($orderId);
        $this->assertInstanceOfResource(RespondSuccess::class, $acknowledgeResult);

        // Step 3: Capture partial amount
        $captureData = [
            'captured_amount' => 15000,
            'description' => 'Partial capture for shipped items'
        ];
        $captureResponse = ['capture_id' => 'capture_abc123'];
        $this->mockApiResponse($captureResponse);

        $captureResult = $this->klarna->createCapture($orderId, $captureData);
        $this->assertInstanceOfResource(RespondSuccess::class, $captureResult);

        // Step 4: Create refund
        $refundData = [
            'refunded_amount' => 3000,
            'description' => 'Refund for returned item'
        ];
        $refundResponse = ['refund_id' => 'refund_def456'];
        $this->mockApiResponse($refundResponse);

        $refundResult = $this->klarna->createRefund($orderId, $refundData);
        $this->assertInstanceOfResource(RespondSuccess::class, $refundResult);

        // Step 5: Release remaining authorization
        $this->mockApiResponse(['released' => true]);
        $releaseResult = $this->klarna->releaseRemainingAuthorization($orderId);
        $this->assertInstanceOfResource(RespondSuccess::class, $releaseResult);
    }

    public function test_checkout_flow_from_creation_to_completion(): void
    {
        $checkoutData = $this->getSampleCheckoutData();

        // Step 1: Create checkout order
        $createResponse = [
            'order_id' => 'checkout_order_789',
            'status' => 'checkout_incomplete',
            'html_snippet' => '<div id="klarna-checkout-container">Loading...</div>',
            'started_at' => '2023-12-01T10:00:00Z'
        ];
        $this->mockApiResponse($createResponse);

        $checkout = $this->klarna->createCheckoutOrder($checkoutData);
        $this->assertInstanceOfResource(RespondSuccess::class, $checkout);
        $this->assertEquals('checkout_incomplete', $checkout->status);

        // Step 2: Update checkout order
        $updateData = array_merge($checkoutData, [
            'order_amount' => 18000,
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'Updated Product',
                    'quantity' => 1,
                    'unit_price' => 15000,
                    'total_amount' => 15000
                ],
                [
                    'type' => 'shipping_fee',
                    'name' => 'Express Shipping',
                    'quantity' => 1,
                    'unit_price' => 3000,
                    'total_amount' => 3000
                ]
            ]
        ]);
        $updateResponse = [
            'order_id' => 'checkout_order_789',
            'status' => 'checkout_incomplete',
            'order_amount' => 18000
        ];
        $this->mockApiResponse($updateResponse);

        $updatedCheckout = $this->klarna->updateCheckoutOrder('checkout_order_789', $updateData);
        $this->assertInstanceOfResource(RespondSuccess::class, $updatedCheckout);
        $this->assertEquals(18000, $updatedCheckout->orderAmount);

        // Step 3: Get completed checkout order
        $completedResponse = [
            'order_id' => 'checkout_order_789',
            'status' => 'checkout_complete',
            'klarna_reference' => 'checkout_ref_abc',
            'order_amount' => 18000,
            'completed_at' => '2023-12-01T10:05:00Z'
        ];
        $this->mockApiResponse($completedResponse);

        $completedCheckout = $this->klarna->getCheckoutOrder('checkout_order_789');
        $this->assertInstanceOfResource(RespondSuccess::class, $completedCheckout);
        $this->assertEquals('checkout_complete', $completedCheckout->status);
        $this->assertEquals('checkout_ref_abc', $completedCheckout->klarnaReference);
    }

    public function test_customer_token_workflow(): void
    {
        $tokenId = 'customer_token_456';

        // Step 1: Get customer token details
        $tokenResponse = [
            'token_id' => $tokenId,
            'status' => 'active',
            'payment_method_type' => 'card',
            'masked_number' => '****1234',
            'brand' => 'Visa'
        ];
        $this->mockApiResponse($tokenResponse);

        $token = $this->klarna->customerToken($tokenId);
        $this->assertInstanceOfResource(CustomerToken::class, $token);
        $this->assertEquals('active', $token->status);
        $this->assertEquals('card', $token->paymentMethodType);

        // Step 2: Create order using customer token
        $orderData = $this->getSampleOrderData();
        $orderResponse = [
            'order_id' => 'token_order_789',
            'klarna_reference' => 'token_ref_abc',
            'status' => 'AUTHORIZED',
            'fraud_status' => 'ACCEPTED'
        ];
        $this->mockApiResponse($orderResponse);

        $order = $this->klarna->createOrderFromCustomerToken($tokenId, $orderData);
        $this->assertInstanceOfResource(Order::class, $order);
        $this->assertEquals('token_order_789', $order->orderId);
        $this->assertEquals('ACCEPTED', $order->fraudStatus);
    }

    public function test_authorization_token_to_customer_token_flow(): void
    {
        $authToken = 'auth_token_789';

        // Step 1: Create customer token from authorization
        $tokenData = $this->getSampleCustomerTokenData();
        $tokenResponse = [
            'token_id' => 'new_token_123',
            'status' => 'active',
            'payment_method_type' => 'invoice'
        ];
        $this->mockApiResponse($tokenResponse);

        $customerToken = $this->klarna->createCustomerToken($authToken, $tokenData);
        $this->assertInstanceOfResource(CustomerTokenFromAuthorization::class, $customerToken);
        $this->assertEquals('new_token_123', $customerToken->tokenId);

        // Step 2: Create order from authorization (separate flow)
        $orderData = $this->getSampleOrderData();
        $orderResponse = [
            'order_id' => 'auth_order_456',
            'klarna_reference' => 'auth_ref_def',
            'fraud_status' => 'ACCEPTED'
        ];
        $this->mockApiResponse($orderResponse);

        $order = $this->klarna->createOrderFromAuthorizationToken($authToken, $orderData);
        $this->assertInstanceOfResource(OrderCreated::class, $order);
        $this->assertEquals('auth_order_456', $order->orderId);
    }

    public function test_error_handling_throughout_workflow(): void
    {
        // Test validation error during session creation
        $this->mockApiError(422, [
            'error_code' => 'INVALID_DATA',
            'error_messages' => ['purchase_country is required']
        ]);

        $this->expectException(ValidationException::class);
        $this->klarna->createSession(['invalid' => 'data']);
    }

    public function test_not_found_error_handling(): void
    {
        // Test 404 error when getting non-existent order
        $this->mockApiError(404);

        $this->expectException(NotFoundException::class);
        $this->klarna->order('non_existent_order');
    }

    public function test_failed_action_error_handling(): void
    {
        // Test 400 error during capture
        $this->mockApiError(400, ['message' => 'Cannot capture already captured order']);

        $this->expectException(FailedActionException::class);
        $this->klarna->createCapture('order_123', ['captured_amount' => 5000]);
    }

    public function test_multi_region_support(): void
    {
        // Test EU region
        $euKlarna = $this->getKlarnaInstance('EU', 'test');
        $this->assertEquals('https://api.playground.klarna.com/', $euKlarna->getBaseUri('EU', 'test'));

        // Test NA region
        $naKlarna = $this->getKlarnaInstance('NA', 'test');
        $this->assertEquals('https://api-na.playground.klarna.com/', $naKlarna->getBaseUri('NA', 'test'));

        // Test OC region
        $ocKlarna = $this->getKlarnaInstance('OC', 'live');
        $this->assertEquals('https://api-oc.klarna.com/', $ocKlarna->getBaseUri('OC', 'live'));
    }

    public function test_environment_switching(): void
    {
        // Test mode switching affects base URI
        $testKlarna = $this->getKlarnaInstance('EU', 'test');
        $liveKlarna = $this->getKlarnaInstance('EU', 'live');

        $this->assertStringContainsString('playground', $testKlarna->getBaseUri('EU', 'test'));
        $this->assertStringNotContainsString('playground', $liveKlarna->getBaseUri('EU', 'live'));
    }

    public function test_complex_order_with_multiple_line_items(): void
    {
        $complexOrderData = [
            'purchase_country' => 'SE',
            'purchase_currency' => 'SEK',
            'locale' => 'sv-SE',
            'order_amount' => 125000,
            'order_tax_amount' => 25000,
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'Laptop Computer',
                    'quantity' => 1,
                    'unit_price' => 80000,
                    'tax_rate' => 2500,
                    'total_amount' => 80000,
                    'total_tax_amount' => 16000
                ],
                [
                    'type' => 'physical',
                    'name' => 'Wireless Mouse',
                    'quantity' => 2,
                    'unit_price' => 15000,
                    'tax_rate' => 2500,
                    'total_amount' => 30000,
                    'total_tax_amount' => 6000
                ],
                [
                    'type' => 'shipping_fee',
                    'name' => 'Express Delivery',
                    'quantity' => 1,
                    'unit_price' => 15000,
                    'tax_rate' => 2500,
                    'total_amount' => 15000,
                    'total_tax_amount' => 3000
                ]
            ],
            'merchant_reference1' => 'COMPLEX-ORDER-001',
            'merchant_reference2' => 'STORE-REF-ABC'
        ];

        $sessionResponse = [
            'session_id' => 'complex_session_123',
            'client_token' => 'complex_token_456'
        ];
        $this->mockApiResponse($sessionResponse);

        $session = $this->klarna->createSession($complexOrderData);
        $this->assertInstanceOfResource(Session::class, $session);
        $this->assertEquals('complex_session_123', $session->sessionId);

        // Verify the request was made correctly
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals(125000, $requestBody['order_amount']);
        $this->assertCount(3, $requestBody['order_lines']);
        $this->assertEquals('Laptop Computer', $requestBody['order_lines'][0]['name']);
        $this->assertEquals('Express Delivery', $requestBody['order_lines'][2]['name']);
    }

    public function test_date_formatting_utility(): void
    {
        $testDate = '2023-12-25 15:30:45';
        
        // Test default format
        $defaultFormatted = $this->klarna->convertDateFormat($testDate);
        $this->assertEquals('20231225153045', $defaultFormatted);

        // Test custom format
        $isoFormatted = $this->klarna->convertDateFormat($testDate, 'Y-m-d\TH:i:s\Z');
        $this->assertEquals('2023-12-25T15:30:45Z', $isoFormatted);

        // Test another custom format
        $readableFormatted = $this->klarna->convertDateFormat($testDate, 'F j, Y g:i A');
        $this->assertEquals('December 25, 2023 3:30 PM', $readableFormatted);
    }

    public function test_resource_transformation_utility(): void
    {
        $collection = [
            ['id' => 1, 'name' => 'Item 1', 'type' => 'product'],
            ['id' => 2, 'name' => 'Item 2', 'type' => 'service'],
            ['id' => 3, 'name' => 'Item 3', 'type' => 'digital']
        ];

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('transformCollection');
        $method->setAccessible(true);
        $transformed = $method->invoke($this->klarna, $collection, \Vrajroham\PhpKlarna\Resources\ApiResource::class);

        $this->assertIsArray($transformed);
        $this->assertCount(3, $transformed);
        
        foreach ($transformed as $index => $resource) {
            $this->assertInstanceOf(\Vrajroham\PhpKlarna\Resources\ApiResource::class, $resource);
            $this->assertEquals($collection[$index]['id'], $resource->id);
            $this->assertEquals($collection[$index]['name'], $resource->name);
            $this->assertEquals($collection[$index]['type'], $resource->type);
        }
    }

    public function test_end_to_end_recurring_payment_scenario(): void
    {
        // Scenario: Customer makes initial purchase, gets token, then uses token for recurring payment
        
        // Step 1: Create initial session
        $sessionData = $this->getSampleSessionData();
        $sessionResponse = ['session_id' => 'recurring_session_123'];
        $this->mockApiResponse($sessionResponse);
        
        $session = $this->klarna->createSession($sessionData);
        $this->assertEquals('recurring_session_123', $session->sessionId);

        // Step 2: Create customer token from auth
        $tokenData = $this->getSampleCustomerTokenData();
        $tokenResponse = ['token_id' => 'recurring_token_456', 'status' => 'active'];
        $this->mockApiResponse($tokenResponse);
        
        $customerToken = $this->klarna->createCustomerToken('auth_token_123', $tokenData);
        $this->assertEquals('recurring_token_456', $customerToken->tokenId);

        // Step 3: Use token for recurring order
        $recurringOrderData = [
            'purchase_country' => 'US',
            'purchase_currency' => 'USD',
            'locale' => 'en-US',
            'order_amount' => 2999, // Monthly subscription
            'order_lines' => [
                [
                    'type' => 'digital',
                    'name' => 'Monthly Subscription',
                    'quantity' => 1,
                    'unit_price' => 2999,
                    'total_amount' => 2999
                ]
            ],
            'auto_capture' => true
        ];
        
        $recurringOrderResponse = [
            'order_id' => 'recurring_order_789',
            'status' => 'CAPTURED',
            'fraud_status' => 'ACCEPTED'
        ];
        $this->mockApiResponse($recurringOrderResponse);
        
        $recurringOrder = $this->klarna->createOrderFromCustomerToken('recurring_token_456', $recurringOrderData);
        $this->assertEquals('recurring_order_789', $recurringOrder->orderId);
        $this->assertEquals('CAPTURED', $recurringOrder->status);
    }
}