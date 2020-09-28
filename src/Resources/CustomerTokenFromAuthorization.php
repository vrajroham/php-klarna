<?php

namespace Vrajroham\PhpKlarna\Resources;

class CustomerTokenFromAuthorization extends ApiResource 
{
    public string $tokenId;

    public array $billingAddress;

    public function __construct(array $attributes, $klarna = null)
    {
        parent::__construct($attributes, $klarna);        
    }
}