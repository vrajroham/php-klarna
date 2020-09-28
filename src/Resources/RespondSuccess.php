<?php

namespace Vrajroham\PhpKlarna\Resources;

class RespondSuccess extends ApiResource 
{        
    public string $response;

    public function __construct($attributes, $klarna = null)
    {
        if(is_array($attributes)){
            parent::__construct($attributes, $klarna);        
        }else{
            $this->response = $attributes;
        }
    }
}