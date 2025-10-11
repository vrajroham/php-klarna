<?php

namespace Vrajroham\PhpKlarna\Tests\Unit\Actions;

use Vrajroham\PhpKlarna\Resources\RespondSuccess;
use Vrajroham\PhpKlarna\Tests\TestCase;

/**
 * @covers \Vrajroham\PhpKlarna\Actions\ManageCheckouts
 */
class ManageCheckoutsTest extends TestCase
{
    public function test_create_checkout_order_returns_success_response(): void
    {
        $checkoutData = $this->getSampleCheckoutData();
        $responseData = [
            'order_id' => 'checkout_order_123',
            'status' => 'checkout_incomplete',
            'html_snippet' => '<div id="klarna-checkout-container">...</div>',
            'started_at' => '2023-12-01T10:00:00Z'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createCheckoutOrder($checkoutData);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals('checkout_order_123', $result->orderId);
        $this->assertEquals('checkout_incomplete', $result->status);
        $this->assertStringContainsString('klarna-checkout-container', $result->htmlSnippet);
    }

    public function test_create_checkout_order_makes_correct_api_call(): void
    {
        $checkoutData = $this->getSampleCheckoutData();
        $responseData = ['order_id' => 'checkout_order_123'];

        $this->mockApiResponse($responseData);

        $this->klarna->createCheckoutOrder($checkoutData);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString('checkout/v3/orders', (string) $lastRequest->getUri());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals($checkoutData['purchase_country'], $requestBody['purchase_country']);
        $this->assertEquals($checkoutData['order_amount'], $requestBody['order_amount']);
        $this->assertEquals($checkoutData['purchase_currency'], $requestBody['purchase_currency']);
    }

    public function test_get_checkout_order_returns_success_response(): void
    {
        $orderId = 'checkout_order_456';
        $responseData = [
            'order_id' => $orderId,
            'status' => 'checkout_complete',
            'purchase_country' => 'US',
            'purchase_currency' => 'USD',
            'locale' => 'en-US',
            'order_amount' => 15000,
            'order_tax_amount' => 1250,
            'klarna_reference' => 'klarna_ref_789',
            'html_snippet' => '<div id="klarna-checkout-container">Completed</div>',
            'started_at' => '2023-12-01T10:00:00Z',
            'completed_at' => '2023-12-01T10:05:00Z'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->getCheckoutOrder($orderId);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals($orderId, $result->orderId);
        $this->assertEquals('checkout_complete', $result->status);
        $this->assertEquals('klarna_ref_789', $result->klarnaReference);
        $this->assertEquals(15000, $result->orderAmount);
    }

    public function test_get_checkout_order_makes_correct_api_call(): void
    {
        $orderId = 'checkout_order_456';
        $responseData = ['order_id' => $orderId];

        $this->mockApiResponse($responseData);

        $this->klarna->getCheckoutOrder($orderId);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('GET', $lastRequest->getMethod());
        $this->assertStringContainsString("checkout/v3/orders/{$orderId}", (string) $lastRequest->getUri());
    }

    public function test_update_checkout_order_returns_success_response(): void
    {
        $orderId = 'checkout_order_789';
        $updateData = [
            'order_amount' => 18000,
            'order_tax_amount' => 1500,
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'Updated Product',
                    'quantity' => 1,
                    'unit_price' => 15000,
                    'tax_rate' => 1000,
                    'total_amount' => 15000,
                    'total_tax_amount' => 1250
                ],
                [
                    'type' => 'shipping_fee',
                    'name' => 'Express Shipping',
                    'quantity' => 1,
                    'unit_price' => 3000,
                    'tax_rate' => 1000,
                    'total_amount' => 3000,
                    'total_tax_amount' => 250
                ]
            ]
        ];

        $responseData = [
            'order_id' => $orderId,
            'status' => 'checkout_incomplete',
            'order_amount' => 18000,
            'html_snippet' => '<div id="klarna-checkout-container">Updated</div>'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->updateCheckoutOrder($orderId, $updateData);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals($orderId, $result->orderId);
        $this->assertEquals(18000, $result->orderAmount);
        $this->assertStringContainsString('Updated', $result->htmlSnippet);
    }

    public function test_update_checkout_order_makes_correct_api_call(): void
    {
        $orderId = 'checkout_order_789';
        $updateData = [
            'order_amount' => 20000,
            'order_lines' => [
                [
                    'name' => 'Updated Product',
                    'quantity' => 2,
                    'unit_price' => 10000,
                    'total_amount' => 20000
                ]
            ]
        ];

        $responseData = ['order_id' => $orderId];

        $this->mockApiResponse($responseData);

        $this->klarna->updateCheckoutOrder($orderId, $updateData);

        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString("checkout/v3/orders/{$orderId}", (string) $lastRequest->getUri());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals(20000, $requestBody['order_amount']);
        $this->assertEquals('Updated Product', $requestBody['order_lines'][0]['name']);
    }

    public function test_create_checkout_order_with_minimal_data(): void
    {
        $minimalData = [
            'purchase_country' => 'US',
            'purchase_currency' => 'USD',
            'locale' => 'en-US',
            'order_amount' => 5000,
            'order_lines' => [
                [
                    'name' => 'Simple Product',
                    'quantity' => 1,
                    'unit_price' => 5000,
                    'total_amount' => 5000
                ]
            ],
            'merchant_urls' => [
                'terms' => 'https://example.com/terms',
                'checkout' => 'https://example.com/checkout',
                'confirmation' => 'https://example.com/confirmation',
                'push' => 'https://example.com/push'
            ]
        ];

        $responseData = [
            'order_id' => 'minimal_checkout_123',
            'status' => 'checkout_incomplete',
            'html_snippet' => '<div>Minimal checkout</div>'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createCheckoutOrder($minimalData);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals('minimal_checkout_123', $result->orderId);
        $this->assertEquals('checkout_incomplete', $result->status);
    }

    public function test_create_checkout_order_with_options(): void
    {
        $dataWithOptions = array_merge($this->getSampleCheckoutData(), [
            'options' => [
                'allow_separate_shipping_address' => true,
                'date_of_birth_mandatory' => false,
                'require_validate_callback_success' => true,
                'phone_mandatory' => true
            ]
        ]);

        $responseData = [
            'order_id' => 'options_checkout_456',
            'status' => 'checkout_incomplete',
            'html_snippet' => '<div>Checkout with options</div>'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createCheckoutOrder($dataWithOptions);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals('options_checkout_456', $result->orderId);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertTrue($requestBody['options']['allow_separate_shipping_address']);
        $this->assertFalse($requestBody['options']['date_of_birth_mandatory']);
        $this->assertTrue($requestBody['options']['require_validate_callback_success']);
        $this->assertTrue($requestBody['options']['phone_mandatory']);
    }

    public function test_create_checkout_order_with_external_payment_methods(): void
    {
        $dataWithExternalMethods = array_merge($this->getSampleCheckoutData(), [
            'external_payment_methods' => [
                [
                    'name' => 'PayPal',
                    'redirect_url' => 'https://example.com/paypal',
                    'image_url' => 'https://example.com/paypal-logo.png',
                    'fee' => 250
                ],
                [
                    'name' => 'Apple Pay',
                    'redirect_url' => 'https://example.com/applepay',
                    'image_url' => 'https://example.com/applepay-logo.png',
                    'fee' => 0
                ]
            ]
        ]);

        $responseData = [
            'order_id' => 'external_methods_789',
            'status' => 'checkout_incomplete',
            'html_snippet' => '<div>Checkout with external methods</div>'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createCheckoutOrder($dataWithExternalMethods);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals('external_methods_789', $result->orderId);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertCount(2, $requestBody['external_payment_methods']);
        $this->assertEquals('PayPal', $requestBody['external_payment_methods'][0]['name']);
        $this->assertEquals(250, $requestBody['external_payment_methods'][0]['fee']);
        $this->assertEquals('Apple Pay', $requestBody['external_payment_methods'][1]['name']);
        $this->assertEquals(0, $requestBody['external_payment_methods'][1]['fee']);
    }

    public function test_get_checkout_order_incomplete_status(): void
    {
        $orderId = 'incomplete_checkout_123';
        $responseData = [
            'order_id' => $orderId,
            'status' => 'checkout_incomplete',
            'purchase_country' => 'SE',
            'purchase_currency' => 'SEK',
            'locale' => 'sv-SE',
            'order_amount' => 25000,
            'html_snippet' => '<div id="klarna-checkout-container">In progress...</div>',
            'started_at' => '2023-12-01T15:30:00Z'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->getCheckoutOrder($orderId);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals($orderId, $result->orderId);
        $this->assertEquals('checkout_incomplete', $result->status);
        $this->assertEquals('SE', $result->purchaseCountry);
        $this->assertEquals('SEK', $result->purchaseCurrency);
        $this->assertEquals(25000, $result->orderAmount);
        $this->assertObjectNotHasProperty('klarnaReference', $result); // Should not have klarna_reference when incomplete
    }

    public function test_update_checkout_order_with_customer_data(): void
    {
        $orderId = 'customer_update_456';
        $updateData = [
            'billing_address' => [
                'given_name' => 'Anna',
                'family_name' => 'Andersson',
                'email' => 'anna.andersson@example.com',
                'street_address' => 'Storgatan 123',
                'postal_code' => '11122',
                'city' => 'Stockholm',
                'country' => 'SE'
            ],
            'shipping_address' => [
                'given_name' => 'Anna',
                'family_name' => 'Andersson',
                'street_address' => 'Leveransgatan 456',
                'postal_code' => '11133',
                'city' => 'Stockholm',
                'country' => 'SE'
            ]
        ];

        $responseData = [
            'order_id' => $orderId,
            'status' => 'checkout_incomplete',
            'html_snippet' => '<div>Updated with customer data</div>'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->updateCheckoutOrder($orderId, $updateData);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals($orderId, $result->orderId);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('Anna', $requestBody['billing_address']['given_name']);
        $this->assertEquals('anna.andersson@example.com', $requestBody['billing_address']['email']);
        $this->assertEquals('Leveransgatan 456', $requestBody['shipping_address']['street_address']);
    }

    public function test_create_checkout_order_with_merchant_references(): void
    {
        $dataWithReferences = array_merge($this->getSampleCheckoutData(), [
            'merchant_reference1' => 'CHECKOUT-ORDER-001',
            'merchant_reference2' => 'STORE-REF-456'
        ]);

        $responseData = [
            'order_id' => 'ref_checkout_789',
            'status' => 'checkout_incomplete',
            'merchant_reference1' => 'CHECKOUT-ORDER-001',
            'merchant_reference2' => 'STORE-REF-456'
        ];

        $this->mockApiResponse($responseData);

        $result = $this->klarna->createCheckoutOrder($dataWithReferences);

        $this->assertInstanceOfResource(RespondSuccess::class, $result);
        $this->assertEquals('ref_checkout_789', $result->orderId);
        $this->assertEquals('CHECKOUT-ORDER-001', $result->merchantReference1);
        $this->assertEquals('STORE-REF-456', $result->merchantReference2);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('CHECKOUT-ORDER-001', $requestBody['merchant_reference1']);
        $this->assertEquals('STORE-REF-456', $requestBody['merchant_reference2']);
    }
}