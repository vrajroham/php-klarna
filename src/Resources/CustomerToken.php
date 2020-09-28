<?php

namespace Vrajroham\PhpKlarna\Resources;

class CustomerToken extends ApiResource 
{    
    public string $status;

    public string $paymentMethodType;
    
    public function __construct(array $attributes, $klarna = null)
    {
        parent::__construct($attributes, $klarna);        
    }
}