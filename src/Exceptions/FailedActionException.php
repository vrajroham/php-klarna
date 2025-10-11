<?php

namespace Vrajroham\PhpKlarna\Exceptions;

use Exception;

class FailedActionException extends Exception
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
