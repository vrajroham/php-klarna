<?php

namespace Vrajroham\PhpKlarna\Resources;

class OrderCreated extends ApiResource 
{
    public string $orderId;

    public string $redirectUrl;

    public string $fraudStatus;
    
    public array $authorizedPaymentMethod;

    public function __construct(array $attributes, $klarna = null)
    {
        parent::__construct($attributes, $klarna);        
    }
}