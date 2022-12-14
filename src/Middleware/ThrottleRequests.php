<?php

namespace WebmanTech\LaravelCache\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Support\Arr;
use Illuminate\Support\InteractsWithTime;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use WebmanTech\LaravelCache\Exceptions\ThrottleRequestsException;
use WebmanTech\LaravelCache\Facades\RateLimiter as RateLimiterFacade;

/**
 * 参考：https://github.com/laravel/framework/blob/9.x/src/Illuminate/Routing/Middleware/ThrottleRequests.php
 */
class ThrottleRequests implements MiddlewareInterface
{
    use InteractsWithTime;

    /**
     * @var RateLimiter
     */
    protected $limiter;

    /**
     * @var string
     */
    protected $limiterName;

    public function __construct(string $limiterName = null)
    {
        $this->limiter = RateLimiterFacade::instance();
        $this->limiterName = $limiterName ?? RateLimiterFacade::FOR_REQUEST;
    }

    /**
     * @inheritDoc
     */
    public function process(Request $request, callable $handler): Response
    {
        $limiter = $this->limiter->limiter($this->limiterName);
        if ($limiter === null) {
            return $handler($request);
        }
        $limiterResponse = $limiter($request);

        if ($limiterResponse instanceof Response) {
            return $limiterResponse;
        } elseif ($limiterResponse instanceof Unlimited) {
            return $handler($request);
        }

        /** @var Limit[] $limits */
        $limits = collect(Arr::wrap($limiterResponse))->map(function (Limit $limit) {
            $limit->key = $this->limiterName . $limit->key;
            return $limit;
        })->all();

        foreach ($limits as $limit) {
            if ($this->tooManyAttempts($limit)) {
                $exception = $this->buildException($request, $limit);
                if ($exception instanceof Response) {
                    return $exception;
                }
                throw $exception;
            }
        }

        /** @var Response $response */
        $response = $handler($request);

        foreach ($limits as $limit) {
            $response->withHeaders(
                $this->getHeaders(
                    $limit->maxAttempts,
                    $this->calculateRemainingAttempts($limit->key, $limit->maxAttempts),
                    null,
                    $response
                )
            );
        }

        return $response;
    }

    /**
     * @param Limit $limit
     * @return bool
     */
    protected function tooManyAttempts(Limit $limit): bool
    {
        $bool = $this->limiter->tooManyAttempts($limit->key, $limit->maxAttempts);
        if (!$bool) {
            $this->limiter->hit($limit->key, $limit->decayMinutes * 60);
        }
        return $bool;
    }

    /**
     * @param Request $request
     * @param Limit $limit
     * @return ThrottleRequestsException|Response|\Throwable
     */
    protected function buildException(Request $request, Limit $limit)
    {
        $retryAfter = $this->getTimeUntilNextRetry($limit->key);

        $headers = $this->getHeaders(
            $limit->maxAttempts,
            $this->calculateRemainingAttempts($limit->key, $limit->maxAttempts, $retryAfter),
            $retryAfter
        );

        $responseCallback = $limit->responseCallback;
        return is_callable($responseCallback)
            ? $responseCallback($request, $headers)
            : new ThrottleRequestsException('Too Many Attempts.', $headers);
    }

    /**
     * @param string $key
     * @return int
     */
    protected function getTimeUntilNextRetry(string $key): int
    {
        return $this->limiter->availableIn($key);
    }

    /**
     * @param int $maxAttempts
     * @param int $remainingAttempts
     * @param int|null $retryAfter
     * @param Response|null $response
     * @return array
     */
    protected function getHeaders(int $maxAttempts, int $remainingAttempts, ?int $retryAfter = null, ?Response $response = null): array
    {
        if ($response &&
            !is_null($response->getHeader('X-RateLimit-Remaining')) &&
            (int)$response->getHeader('X-RateLimit-Remaining') <= $remainingAttempts) {
            return [];
        }

        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ];

        if (!is_null($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
            $headers['X-RateLimit-Reset'] = $this->availableAt($retryAfter);
        }

        return $headers;
    }

    /**
     * @param string $key
     * @param int $maxAttempts
     * @param int|null $retryAfter
     * @return int
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts, ?int $retryAfter = null): int
    {
        return is_null($retryAfter) ? $this->limiter->retriesLeft($key, $maxAttempts) : 0;
    }
}
