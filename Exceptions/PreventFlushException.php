<?php

namespace WebmanTech\LaravelCache\Exceptions;

use RuntimeException;
use Throwable;

class PreventFlushException extends RuntimeException
{
    public function __construct($message = 'Forbidden to use flush() method', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
