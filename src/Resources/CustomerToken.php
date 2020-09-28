<?php

namespace Vrajroham\PhpKlarna\Resources;

class CustomerToken extends ApiResource 
{
    public string $tokenId;

    public string $redirectUrl;

    public function __construct(array $attributes, $klarna = null)
    {
        parent::__construct($attributes, $klarna);        
    }
}