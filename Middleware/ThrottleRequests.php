<?php

namespace WebmanTech\LaravelCache\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Support\Arr;
use Illuminate\Support\InteractsWithTime;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Webman\Http\Response as WebmanResponse;
use WebmanTech\CommonUtils\Middleware\BaseMiddleware;
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Response;
use WebmanTech\LaravelCache\Exceptions\ThrottleRequestsException;
use WebmanTech\LaravelCache\Facades\CacheRateLimiter;

/**
 * @see https://github.com/laravel/framework/blob/11.x/src/Illuminate/Routing/Middleware/ThrottleRequests.php
 */
class ThrottleRequests extends BaseMiddleware
{
    use InteractsWithTime;

    /**
     * @var RateLimiter
     */
    protected $limiter;

    /**
     * @var array
     */
    protected $config = [
        'limiter_for' => null,
        'with_headers' => true,
    ];

    public function __construct(array $config = [])
    {
        $this->limiter = CacheRateLimiter::instance();
        $this->config = array_merge($this->config, $config);
        if ($this->config['limiter_for'] === null) {
            $this->config['limiter_for'] = CacheRateLimiter::FOR_REQUEST;
        }
    }

    /**
     * @inheritDoc
     */
    public function processRequest(Request $request, Closure $handler): Response
    {
        $limiter = $this->limiter->limiter($this->config['limiter_for']);
        if ($limiter === null) {
            return $handler($request);
        }
        // limiter 回调保持使用宿主原始请求对象
        $limiterResponse = $limiter($request->getRaw());

        // 回调可直接返回响应：CommonUtils 包装或宿主原始 Response（webman 下与历史版本行为一致）
        if ($limiterResponse instanceof Response
            || $limiterResponse instanceof WebmanResponse
            || $limiterResponse instanceof SymfonyResponse
        ) {
            return Response::from($limiterResponse);
        } elseif ($limiterResponse instanceof Unlimited) {
            return $handler($request);
        }

        /** @var Limit[] $limits */
        $limits = collect(Arr::wrap($limiterResponse))->map(function (Limit $limit) {
            $limit->key = $this->config['limiter_for'] . ':' . $limit->key;
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
            $this->limiter->hit($limit->key, $limit->decaySeconds);
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
        /** @phpstan-ignore-next-line */
        return is_callable($responseCallback)
            ? Response::from($responseCallback($request->getRaw(), $headers))
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
        if (!$this->config['with_headers']) {
            return [];
        }

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
