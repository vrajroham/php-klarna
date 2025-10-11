# PHP Klarna SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vrajroham/php-klarna.svg?style=flat-square)](https://packagist.org/packages/vrajroham/php-klarna)
[![Build Status](https://img.shields.io/travis/vrajroham/php-klarna/master.svg?style=flat-square)](https://travis-ci.org/vrajroham/php-klarna)
[![Quality Score](https://img.shields.io/scrutinizer/g/vrajroham/php-klarna.svg?style=flat-square)](https://scrutinizer-ci.com/g/vrajroham/php-klarna)
[![Total Downloads](https://img.shields.io/packagist/dt/vrajroham/php-klarna.svg?style=flat-square)](https://packagist.org/packages/vrajroham/php-klarna)
[![PHP Composer](https://github.com/vrajroham/php-klarna/actions/workflows/php.yml/badge.svg)](https://github.com/vrajroham/php-klarna/actions/workflows/php.yml)

A comprehensive PHP SDK for integrating with Klarna's REST APIs. This package provides a clean, intuitive interface for all major Klarna services including Payments, Orders, Checkouts, and Customer Token management.

## 🚀 Features

- **Complete API Coverage**: Support for Payments, Orders, Checkouts, and Customer Tokens APIs
- **Multi-Region Support**: Works with EU, North America (NA), and Oceania (OC) regions
- **Environment Flexibility**: Easy switching between test (playground) and live environments
- **Type-Safe**: Built with proper PHP typing and comprehensive error handling
- **Developer Friendly**: Intuitive method names and comprehensive documentation
- **PSR-4 Compliant**: Follows PHP-FIG standards for autoloading
- **Modern PHP**: Requires PHP 8.0+ with latest dependencies

## 📋 Requirements

- PHP 8.0 or higher
- Guzzle HTTP Client 6.5+ or 7.1+
- Carbon 2.40+ for date handling
- Valid Klarna API credentials

## 🔐 Prerequisites & Authentication

### Getting Klarna API Credentials

Before using this SDK, you need to obtain API credentials from Klarna:

1. **For Testing**: Sign up for a [Klarna Playground account](https://docs.klarna.com/resources/developer-tools/testing-payments/before-you-test/)
2. **For Production**: Contact Klarna to get live API credentials

### Authentication Details

Klarna uses **HTTP Basic Authentication** with:
- **Username**: Your Klarna API username (UID)
- **Password**: Your Klarna API password

### Required Headers

The SDK automatically sets these headers:
```
Authorization: Basic {base64(username:password)}
Accept: application/json
Content-Type: application/json
```

### Regions & Endpoints

| Region | Code | Test Base URL | Live Base URL |
|--------|------|---------------|---------------|
| Europe | `EU` | `https://api.playground.klarna.com/` | `https://api.klarna.com/` |
| North America | `NA` | `https://api-na.playground.klarna.com/` | `https://api-na.klarna.com/` |
| Oceania | `OC` | `https://api-oc.playground.klarna.com/` | `https://api-oc.klarna.com/` |

## 📦 Installation

Install the package via Composer:

```bash
composer require vrajroham/php-klarna
```

## ⚡ Quick Start

### Basic Setup

```php
<?php
use Vrajroham\PhpKlarna\PhpKlarna;

// Initialize for EU region in test mode
$klarna = new PhpKlarna(
    'your-api-username',
    'your-api-password',
    'EU',    // Region: EU, NA, or OC
    'test'   // Mode: test or live
);

// For production (live mode)
$klarna = new PhpKlarna(
    'your-live-username',
    'your-live-password',
    'EU',
    'live'
);
```

### Environment Variables (Recommended)

Create a `.env` file:
```env
KLARNA_USERNAME=your-api-username
KLARNA_PASSWORD=your-api-password
KLARNA_REGION=EU
KLARNA_MODE=test
```

```php
$klarna = new PhpKlarna(
    $_ENV['KLARNA_USERNAME'],
    $_ENV['KLARNA_PASSWORD'],
    $_ENV['KLARNA_REGION'],
    $_ENV['KLARNA_MODE']
);
```

### Simple Payment Flow Example

```php
// 1. Create a payment session
$sessionData = [
    'purchase_country' => 'US',
    'purchase_currency' => 'USD',
    'locale' => 'en-US',
    'order_amount' => 10000, // $100.00 in cents
    'order_lines' => [
        [
            'name' => 'Test Product',
            'quantity' => 1,
            'unit_price' => 10000,
            'total_amount' => 10000
        ]
    ]
];

$session = $klarna->createSession($sessionData);
echo "Session ID: " . $session->session_id;

// 2. After customer completes payment, create order
$orderData = [
    'purchase_country' => 'US',
    'purchase_currency' => 'USD',
    'locale' => 'en-US',
    'order_amount' => 10000,
    'order_lines' => $sessionData['order_lines']
];

$order = $klarna->createOrderFromAuthorizationToken(
    'auth-token-from-frontend',
    $orderData
);

echo "Order ID: " . $order->order_id;
```

## 📚 Complete API Reference

### 💳 Payments API

The Payments API handles the core payment flow including sessions, authorizations, and order creation.

#### Create Payment Session

Creates a new payment session for Klarna Payments.

```php
$sessionData = [
    'purchase_country' => 'US',
    'purchase_currency' => 'USD',
    'locale' => 'en-US',
    'order_amount' => 50000, // $500.00 in cents
    'order_tax_amount' => 4167, // Tax amount in cents
    'order_lines' => [
        [
            'type' => 'physical',
            'name' => 'Premium Headphones',
            'quantity' => 2,
            'unit_price' => 25000,
            'tax_rate' => 1000, // 10% = 1000
            'total_amount' => 50000,
            'total_discount_amount' => 0,
            'total_tax_amount' => 4167
        ]
    ],
    'merchant_urls' => [
        'terms' => 'https://example.com/terms',
        'checkout' => 'https://example.com/checkout',
        'confirmation' => 'https://example.com/confirmation',
        'push' => 'https://example.com/push'
    ]
];

try {
    $session = $klarna->createSession($sessionData);
    echo "Session created successfully!\n";
    echo "Session ID: " . $session->session_id . "\n";
    echo "Client Token: " . $session->client_token . "\n";
} catch (Exception $e) {
    echo "Error creating session: " . $e->getMessage();
}
```

#### Create Order from Authorization Token

After customer completes payment, convert the authorization to an order.

```php
$authToken = 'klarna-auth-token-from-frontend';
$orderData = [
    'purchase_country' => 'US',
    'purchase_currency' => 'USD',
    'locale' => 'en-US',
    'order_amount' => 50000,
    'order_tax_amount' => 4167,
    'order_lines' => [
        [
            'type' => 'physical',
            'name' => 'Premium Headphones',
            'quantity' => 2,
            'unit_price' => 25000,
            'tax_rate' => 1000,
            'total_amount' => 50000,
            'total_tax_amount' => 4167
        ]
    ],
    'merchant_reference1' => 'ORDER-12345',
    'merchant_reference2' => 'STORE-ABC'
];

try {
    $order = $klarna->createOrderFromAuthorizationToken($authToken, $orderData);
    echo "Order created successfully!\n";
    echo "Order ID: " . $order->order_id . "\n";
    echo "Klarna Reference: " . $order->klarna_reference . "\n";
} catch (Exception $e) {
    echo "Error creating order: " . $e->getMessage();
}
```

#### Create Customer Token

Create a reusable customer token for future purchases.

```php
$authToken = 'klarna-auth-token-from-frontend';
$tokenData = [
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

try {
    $customerToken = $klarna->createCustomerToken($authToken, $tokenData);
    echo "Customer token created: " . $customerToken->token_id . "\n";
} catch (Exception $e) {
    echo "Error creating customer token: " . $e->getMessage();
}
```

#### Cancel Authorization

Cancel an unused authorization token.

```php
$authToken = 'klarna-auth-token-to-cancel';

try {
    $result = $klarna->cancelAuthorization($authToken, []);
    echo "Authorization cancelled successfully\n";
} catch (Exception $e) {
    echo "Error cancelling authorization: " . $e->getMessage();
}
```

### 📦 Orders Management API

The Orders API handles post-purchase order management including captures, refunds, and order updates.

#### Get Order Details

Retrieve complete order information.

```php
$orderId = 'klarna-order-id';

try {
    $order = $klarna->order($orderId);
    echo "Order Status: " . $order->status . "\n";
    echo "Order Amount: " . $order->order_amount . "\n";
    echo "Captured Amount: " . $order->captured_amount . "\n";
    echo "Refunded Amount: " . $order->refunded_amount . "\n";
} catch (Exception $e) {
    echo "Error retrieving order: " . $e->getMessage();
}
```

#### Capture Payment

Capture all or part of an authorized order.

```php
$orderId = 'klarna-order-id';
$captureData = [
    'captured_amount' => 25000, // $250.00 in cents
    'description' => 'Partial capture for shipped items',
    'order_lines' => [
        [
            'type' => 'physical',
            'name' => 'Premium Headphones',
            'quantity' => 1,
            'unit_price' => 25000,
            'total_amount' => 25000
        ]
    ]
];

try {
    $capture = $klarna->createCapture($orderId, $captureData);
    echo "Capture successful!\n";
    echo "Capture ID: " . $capture->capture_id . "\n";
} catch (Exception $e) {
    echo "Error capturing payment: " . $e->getMessage();
}
```

#### Refund Payment

Process a full or partial refund.

```php
$orderId = 'klarna-order-id';
$refundData = [
    'refunded_amount' => 10000, // $100.00 refund
    'description' => 'Product return refund',
    'order_lines' => [
        [
            'type' => 'physical',
            'name' => 'Returned Item',
            'quantity' => 1,
            'unit_price' => 10000,
            'total_amount' => 10000
        ]
    ]
];

try {
    $refund = $klarna->createRefund($orderId, $refundData);
    echo "Refund processed successfully!\n";
} catch (Exception $e) {
    echo "Error processing refund: " . $e->getMessage();
}
```

#### Order Lifecycle Management

```php
$orderId = 'klarna-order-id';

// Acknowledge order (confirm you received it)
try {
    $klarna->acknowledgeOrder($orderId);
    echo "Order acknowledged\n";
} catch (Exception $e) {
    echo "Error acknowledging order: " . $e->getMessage();
}

// Cancel order (if not captured)
try {
    $klarna->cancelOrder($orderId);
    echo "Order cancelled\n";
} catch (Exception $e) {
    echo "Error cancelling order: " . $e->getMessage();
}

// Extend authorization time (add 7 days)
try {
    $klarna->extendAuthorizationTime($orderId);
    echo "Authorization time extended\n";
} catch (Exception $e) {
    echo "Error extending authorization: " . $e->getMessage();
}

// Release remaining authorization
try {
    $klarna->releaseRemainingAuthorization($orderId);
    echo "Remaining authorization released\n";
} catch (Exception $e) {
    echo "Error releasing authorization: " . $e->getMessage();
}
```

#### Update Order Information

```php
$orderId = 'klarna-order-id';

// Update merchant references
$merchantData = [
    'merchant_reference1' => 'NEW-ORDER-REF-123',
    'merchant_reference2' => 'UPDATED-STORE-REF'
];

try {
    $klarna->updateMerchantReferences($orderId, $merchantData);
    echo "Merchant references updated\n";
} catch (Exception $e) {
    echo "Error updating merchant references: " . $e->getMessage();
}

// Update customer details
$customerData = [
    'billing_address' => [
        'given_name' => 'John',
        'family_name' => 'Doe',
        'email' => 'john.doe.updated@example.com',
        'street_address' => '456 New Address St',
        'postal_code' => '54321',
        'city' => 'New City',
        'region' => 'NY',
        'country' => 'US'
    ],
    'shipping_address' => [
        'given_name' => 'John',
        'family_name' => 'Doe',
        'street_address' => '456 New Address St',
        'postal_code' => '54321',
        'city' => 'New City',
        'region' => 'NY',
        'country' => 'US'
    ]
];

try {
    $klarna->updateCustomerUpdate($orderId, $customerData);
    echo "Customer details updated\n";
} catch (Exception $e) {
    echo "Error updating customer details: " . $e->getMessage();
}
```

### 🛒 Checkout API

The Checkout API provides a complete checkout solution with Klarna's hosted checkout.

#### Create Checkout Order

Create a new checkout session with a complete shopping experience.

```php
$checkoutData = [
    'purchase_country' => 'US',
    'purchase_currency' => 'USD',
    'locale' => 'en-US',
    'order_amount' => 75000, // $750.00
    'order_tax_amount' => 6250,
    'order_lines' => [
        [
            'type' => 'physical',
            'name' => 'Wireless Mouse',
            'quantity' => 3,
            'unit_price' => 25000,
            'tax_rate' => 1000,
            'total_amount' => 75000,
            'total_tax_amount' => 6250
        ]
    ],
    'merchant_urls' => [
        'terms' => 'https://example.com/terms',
        'checkout' => 'https://example.com/checkout?order_id={checkout.order.id}',
        'confirmation' => 'https://example.com/confirmation?order_id={checkout.order.id}',
        'push' => 'https://example.com/push?order_id={checkout.order.id}'
    ],
    'options' => [
        'allow_separate_shipping_address' => true,
        'date_of_birth_mandatory' => false,
        'require_validate_callback_success' => true
    ],
    'external_payment_methods' => [
        [
            'name' => 'PayPal',
            'redirect_url' => 'https://example.com/paypal',
            'image_url' => 'https://example.com/paypal-logo.png'
        ]
    ]
];

try {
    $checkout = $klarna->createCheckoutOrder($checkoutData);
    echo "Checkout created successfully!\n";
    echo "Order ID: " . $checkout->order_id . "\n";
    echo "HTML Snippet: " . $checkout->html_snippet . "\n";
} catch (Exception $e) {
    echo "Error creating checkout: " . $e->getMessage();
}
```

#### Retrieve Checkout Order

Get the current state of a checkout order.

```php
$checkoutOrderId = 'checkout-order-id';

try {
    $checkout = $klarna->getCheckoutOrder($checkoutOrderId);
    echo "Checkout Status: " . $checkout->status . "\n";
    echo "Order Amount: " . $checkout->order_amount . "\n";
    
    if ($checkout->status === 'checkout_complete') {
        echo "Klarna Reference: " . $checkout->klarna_reference . "\n";
    }
} catch (Exception $e) {
    echo "Error retrieving checkout: " . $e->getMessage();
}
```

#### Update Checkout Order

Modify an existing checkout order (only possible before completion).

```php
$checkoutOrderId = 'checkout-order-id';
$updateData = [
    'order_amount' => 85000, // Updated amount
    'order_tax_amount' => 7083,
    'order_lines' => [
        [
            'type' => 'physical',
            'name' => 'Wireless Mouse',
            'quantity' => 3,
            'unit_price' => 25000,
            'tax_rate' => 1000,
            'total_amount' => 75000,
            'total_tax_amount' => 6250
        ],
        [
            'type' => 'shipping_fee',
            'name' => 'Express Shipping',
            'quantity' => 1,
            'unit_price' => 10000,
            'tax_rate' => 1000,
            'total_amount' => 10000,
            'total_tax_amount' => 833
        ]
    ]
];

try {
    $updated = $klarna->updateCheckoutOrder($checkoutOrderId, $updateData);
    echo "Checkout updated successfully!\n";
} catch (Exception $e) {
    echo "Error updating checkout: " . $e->getMessage();
}
```

### 👤 Customer Tokens API

Manage stored customer payment methods for recurring purchases.

#### Get Customer Token

Retrieve details about a stored customer token.

```php
$tokenId = 'customer-token-id';

try {
    $token = $klarna->customerToken($tokenId);
    echo "Token Status: " . $token->status . "\n";
    echo "Payment Method: " . $token->payment_method_type . "\n";
    echo "Masked Number: " . $token->masked_number . "\n";
} catch (Exception $e) {
    echo "Error retrieving customer token: " . $e->getMessage();
}
```

#### Create Order from Customer Token

Use a stored customer token to create a new order.

```php
$tokenId = 'customer-token-id';
$orderData = [
    'purchase_country' => 'US',
    'purchase_currency' => 'USD',
    'locale' => 'en-US',
    'order_amount' => 35000,
    'order_tax_amount' => 2917,
    'order_lines' => [
        [
            'type' => 'physical',
            'name' => 'Monthly Subscription Box',
            'quantity' => 1,
            'unit_price' => 35000,
            'tax_rate' => 1000,
            'total_amount' => 35000,
            'total_tax_amount' => 2917
        ]
    ],
    'merchant_reference1' => 'SUBSCRIPTION-789',
    'auto_capture' => true
];

try {
    $order = $klarna->createOrderFromCustomerToken($tokenId, $orderData);
    echo "Recurring order created!\n";
    echo "Order ID: " . $order->order_id . "\n";
    echo "Fraud Status: " . $order->fraud_status . "\n";
} catch (Exception $e) {
    echo "Error creating order from token: " . $e->getMessage();
}
```

## 🔧 Advanced Usage

### Custom HTTP Client Configuration

Customize the Guzzle HTTP client for specific needs:

```php
use GuzzleHttp\Client;

$klarna = new PhpKlarna('username', 'password', 'EU', 'test');

// Create custom client with additional options
$customClient = new Client([
    'base_uri' => $klarna->getBaseUri('EU', 'test'),
    'timeout' => 30,
    'verify' => true, // Enable SSL verification
    'headers' => [
        'Authorization' => 'Basic ' . base64_encode('username:password'),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'User-Agent' => 'MyApp/1.0'
    ]
]);

$klarna->setClient($customClient);
```

### Error Handling

The SDK provides specific exceptions for different error scenarios:

```php
use Vrajroham\PhpKlarna\Exceptions\ValidationException;
use Vrajroham\PhpKlarna\Exceptions\NotFoundException;
use Vrajroham\PhpKlarna\Exceptions\FailedActionException;

try {
    $order = $klarna->order('invalid-order-id');
} catch (ValidationException $e) {
    // Handle validation errors (HTTP 422)
    echo "Validation Error: " . json_encode($e->getErrors());
} catch (NotFoundException $e) {
    // Handle not found errors (HTTP 404)
    echo "Resource not found";
} catch (FailedActionException $e) {
    // Handle other API errors (HTTP 400)
    echo "API Error: " . $e->getMessage();
} catch (Exception $e) {
    // Handle any other errors
    echo "Unexpected Error: " . $e->getMessage();
}
```

### Date Formatting Utilities

The SDK includes utilities for working with Klarna's date formats:

```php
// Convert date to Klarna format
$klarnaDate = $klarna->convertDateFormat('2023-12-25 15:30:00', 'Y-m-d\TH:i:s\Z');
echo $klarnaDate; // 2023-12-25T15:30:00Z

// Default format (YmdHis)
$defaultFormat = $klarna->convertDateFormat('2023-12-25 15:30:00');
echo $defaultFormat; // 20231225153000
```

### Webhook Validation

While not directly part of this SDK, here's how to validate Klarna webhooks:

```php
function validateKlarnaWebhook($payload, $signature, $secret) {
    $calculatedSignature = base64_encode(
        hash_hmac('sha256', $payload, $secret, true)
    );
    
    return hash_equals($signature, $calculatedSignature);
}

// In your webhook endpoint
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_KLARNA_HMAC_SHA256'] ?? '';

if (validateKlarnaWebhook($payload, $signature, 'your-webhook-secret')) {
    $data = json_decode($payload, true);
    // Process webhook data
} else {
    http_response_code(401);
    exit('Invalid signature');
}
```

### Best Practices

1. **Environment Management**: Always use environment variables for credentials
2. **Error Handling**: Implement comprehensive error handling for all API calls
3. **Logging**: Log API responses for debugging and audit trails
4. **Idempotency**: Use unique merchant references to prevent duplicate orders
5. **Security**: Never store API credentials in version control
6. **Testing**: Always test thoroughly in playground environment before going live

```php
// Example with comprehensive error handling and logging
function createKlarnaOrder($authToken, $orderData, $logger = null) {
    try {
        $klarna = new PhpKlarna(
            $_ENV['KLARNA_USERNAME'],
            $_ENV['KLARNA_PASSWORD'],
            $_ENV['KLARNA_REGION'],
            $_ENV['KLARNA_MODE']
        );
        
        $order = $klarna->createOrderFromAuthorizationToken($authToken, $orderData);
        
        if ($logger) {
            $logger->info('Klarna order created', [
                'order_id' => $order->order_id,
                'klarna_reference' => $order->klarna_reference,
                'amount' => $orderData['order_amount']
            ]);
        }
        
        return $order;
        
    } catch (ValidationException $e) {
        if ($logger) {
            $logger->error('Klarna validation error', ['errors' => $e->getErrors()]);
        }
        throw new Exception('Invalid order data provided');
        
    } catch (Exception $e) {
        if ($logger) {
            $logger->error('Klarna API error', ['message' => $e->getMessage()]);
        }
        throw new Exception('Payment processing failed');
    }
}
```

## 🛠️ Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

### Testing Your Integration

1. **Use Playground Environment**: Always test with Klarna's playground environment first
2. **Test Error Scenarios**: Verify your error handling works correctly
3. **Validate Webhooks**: Test webhook validation and processing
4. **Test All Flows**: Test complete payment flows from session to capture/refund

Example test with PHPUnit:

```php
class KlarnaIntegrationTest extends TestCase
{
    private $klarna;
    
    protected function setUp(): void
    {
        $this->klarna = new PhpKlarna(
            'test-username',
            'test-password',
            'EU',
            'test'
        );
    }
    
    public function testCreateSession()
    {
        $sessionData = [
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
            ]
        ];
        
        $session = $this->klarna->createSession($sessionData);
        
        $this->assertNotNull($session->session_id);
        $this->assertNotNull($session->client_token);
    }
}
```

## 🆘 Troubleshooting & FAQ

### Common Issues

#### Authentication Errors
**Problem**: Getting 401 Unauthorized errors
**Solution**: 
- Verify your username and password are correct
- Check that you're using the right region (EU/NA/OC)
- Ensure you're using test credentials with playground URLs

#### Invalid Region Errors
**Problem**: `ValidationException` about invalid region
**Solution**: Use uppercase region codes: 'EU', 'NA', or 'OC'

#### SSL Certificate Issues
**Problem**: SSL verification failures in development
**Solution**: 
```php
// Only for development - never in production
$customClient = new Client([
    'verify' => false // Disable SSL verification
]);
$klarna->setClient($customClient);
```

#### Order Not Found Errors
**Problem**: `NotFoundException` when accessing orders
**Solution**: 
- Verify the order ID is correct
- Check that the order was created in the same environment (test/live)
- Ensure sufficient time has passed for order creation to complete

### FAQ

**Q: Can I use this package with Laravel?**
A: Yes! This package works perfectly with Laravel. Add credentials to your `.env` file and use dependency injection.

**Q: How do I handle webhooks?**
A: Klarna sends webhooks for order events. Implement webhook validation using HMAC-SHA256 as shown in the advanced usage section.

**Q: What's the difference between Payments and Checkout APIs?**
A: Payments API gives you more control over the UI, while Checkout API provides Klarna's hosted checkout experience.

**Q: Can I test without real money?**
A: Yes! Use the playground environment with test credentials. No real money is processed.

**Q: How do I go from test to production?**
A: Change the mode from 'test' to 'live' and use your production credentials from Klarna.

**Q: What currencies are supported?**
A: Klarna supports many currencies. Check [Klarna's documentation](https://developers.klarna.com/) for the complete list.

**Q: How do I handle different regions?**
A: Set the appropriate region (EU/NA/OC) when initializing the client based on your merchant account.

### Getting Help

- **Documentation**: [Klarna Developers](https://developers.klarna.com/)
- **Issues**: [GitHub Issues](https://github.com/vrajroham/php-klarna/issues)
- **Email**: vaibhavraj@vrajroham.me

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes.

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details on how to contribute.

### Security

If you discover any security related issues, please email vaibhavraj@vrajroham.me instead of using the issue tracker.

## 👥 Credits

- [Vaibhavraj Roham](https://github.com/vrajroham)
- [All Contributors](../../contributors)

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

**Built with ❤️ for the PHP community**
