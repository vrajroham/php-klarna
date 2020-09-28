<?php

namespace Vrajroham\PhpKlarna\Resources;

class Authorization extends ApiResource 
{
    public string $sessionId;

    public string $clientToken;

    public array $paymentMethodCategories;

    public function __construct(array $attributes, $klarna = null)
    {
        parent::__construct($attributes, $klarna);        
    }
}