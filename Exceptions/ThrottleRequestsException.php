<?php

namespace WebmanTech\LaravelCache\Exceptions;

use Throwable;

class ThrottleRequestsException extends \Exception
{
    private int $statusCode;
    /**
     * @var array<string, mixed>
     */
    private array $headers;

    /**
     * @param array<string, mixed> $headers
     */
    public function __construct(string $message = "", array $headers = [], int $statusCode = 429, int $code = 0, ?Throwable $previous = null)
    {
        $this->headers = $headers;
        $this->statusCode = $statusCode;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
