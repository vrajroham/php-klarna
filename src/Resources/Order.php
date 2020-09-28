<?php

namespace Vrajroham\PhpKlarna\Resources;

class Order extends ApiResource 
{
    public string $orderId;
    public string $status;
    public string $fraudStatus;    
    public array $orderLines;
    public string $orderAmount;
    public string $capturedAmount;
    public string $refundedAmount;
    public string $purchaseCurrency;
    public string $remainingAuthorizedAmount;
    public string $originalOrderAmount;
    public string $merchantReference1;
    public string $merchantReference2;
    public $customer, $billingAddress, $shippingAddress, $merchantData, $initialPaymentMethod;
    public string $createdAt, $expiresAt;
    public array $captures;
    public array $refunds;

    public function __construct(array $attributes, $klarna = null)
    {
        parent::__construct($attributes, $klarna);        
    }
}