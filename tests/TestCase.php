<?php

namespace Vrajroham\PhpKlarna\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Vrajroham\PhpKlarna\PhpKlarna;

abstract class TestCase extends BaseTestCase
{
    protected PhpKlarna $klarna;
    protected MockHandler $mockHandler;
    protected Client $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->mockClient = new Client([
            'handler' => $handlerStack,
            'http_errors' => false
        ]);
        
        $this->klarna = new PhpKlarna('test-username', 'test-password', 'EU', 'test');
        $this->klarna->setClient($this->mockClient);
    }

    protected function mockApiResponse(array $data, int $statusCode = 200, array $headers = []): void
    {
        $this->mockHandler->append(
            new Response($statusCode, $headers, json_encode($data))
        );
    }

    protected function mockApiError(int $statusCode, array $data = []): void
    {
        $this->mockHandler->append(
            new Response($statusCode, [], json_encode($data))
        );
    }

    protected function getKlarnaInstance(string $region = 'EU', string $mode = 'test'): PhpKlarna
    {
        $klarna = new PhpKlarna('test-username', 'test-password', $region, $mode);
        $klarna->setClient($this->mockClient);
        return $klarna;
    }

    protected function getSampleOrderData(): array
    {
        return [
            'purchase_country' => 'US',
            'purchase_currency' => 'USD',
            'locale' => 'en-US',
            'order_amount' => 10000,
            'order_tax_amount' => 833,
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'Test Product',
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'tax_rate' => 1000,
                    'total_amount' => 10000,
                    'total_tax_amount' => 833
                ]
            ],
            'merchant_reference1' => 'TEST-ORDER-001'
        ];
    }

    protected function getSampleSessionData(): array
    {
        return [
            'purchase_country' => 'US',
            'purchase_currency' => 'USD',
            'locale' => 'en-US',
            'order_amount' => 10000,
            'order_lines' => [
                [
                    'name' => 'Test Product',
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'total_amount' => 10000
                ]
            ],
            'merchant_urls' => [
                'terms' => 'https://example.com/terms',
                'checkout' => 'https://example.com/checkout',
                'confirmation' => 'https://example.com/confirmation',
                'push' => 'https://example.com/push'
            ]
        ];
    }

    protected function getSampleCheckoutData(): array
    {
        return [
            'purchase_country' => 'US',
            'purchase_currency' => 'USD',
            'locale' => 'en-US',
            'order_amount' => 15000,
            'order_tax_amount' => 1250,
            'order_lines' => [
                [
                    'type' => 'physical',
                    'name' => 'Checkout Product',
                    'quantity' => 1,
                    'unit_price' => 15000,
                    'tax_rate' => 1000,
                    'total_amount' => 15000,
                    'total_tax_amount' => 1250
                ]
            ],
            'merchant_urls' => [
                'terms' => 'https://example.com/terms',
                'checkout' => 'https://example.com/checkout?order_id={checkout.order.id}',
                'confirmation' => 'https://example.com/confirmation?order_id={checkout.order.id}',
                'push' => 'https://example.com/push?order_id={checkout.order.id}'
            ]
        ];
    }

    protected function getSampleCustomerTokenData(): array
    {
        return [
            'purchase_country' => 'US',
            'purchase_currency' => 'USD',
            'locale' => 'en-US',
            'billing_address' => [
                'given_name' => 'John',
                'family_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'street_address' => '123 Main St',
                'postal_code' => '12345',
                'city' => 'Anytown',
                'region' => 'CA',
                'country' => 'US'
            ]
        ];
    }

    protected function assertInstanceOfResource(string $expectedClass, $actual): void
    {
        $this->assertInstanceOf($expectedClass, $actual);
        $this->assertInstanceOf('Vrajroham\PhpKlarna\Resources\ApiResource', $actual);
    }
}