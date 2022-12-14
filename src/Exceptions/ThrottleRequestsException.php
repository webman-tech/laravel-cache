<?php

namespace WebmanTech\LaravelCache\Exceptions;

use Throwable;

class ThrottleRequestsException extends \Exception
{
    private $statusCode;
    private $headers;

    public function __construct($message = "", array $headers = [], int $statusCode = 429, $code = 0, Throwable $previous = null)
    {
        $this->headers = $headers;
        $this->statusCode = $statusCode;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}