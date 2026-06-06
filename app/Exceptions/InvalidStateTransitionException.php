<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidStateTransitionException extends RuntimeException
{
    public function __construct(string $from, string $to, string $model)
    {
        parent::__construct("Invalid state transition from '{$from}' to '{$to}' on {$model}.");
    }
}
