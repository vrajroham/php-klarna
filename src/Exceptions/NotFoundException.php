<?php

namespace Vrajroham\PhpKlarna\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    public function __construct(string $message = 'Resource not found', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
