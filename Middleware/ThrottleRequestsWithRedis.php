<?php

namespace WebmanTech\LaravelCache\Middleware;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection as Redis;
use Illuminate\Redis\Limiters\DurationLimiter;
use WebmanTech\LaravelCache\Helper\ExtComponentGetter;

/**
 * 参考：https://github.com/laravel/framework/blob/11.x/src/Illuminate/Routing/Middleware/ThrottleRequestsWithRedis.php
 */
class ThrottleRequestsWithRedis extends ThrottleRequests
{
    /**
     * @var array
     */
    public $decaysAt = [];

    /**
     * @var array
     */
    public $remaining = [];

    public function __construct(array $config = [])
    {
        $this->config['redis_connection_name'] = null;
        parent::__construct($config);
    }

    protected function getRedis(): Redis
    {
        $factory = ExtComponentGetter::get(RedisFactory::class);
        if (!$factory instanceof RedisFactory) {
            throw new \RuntimeException(sprintf(
                '%s not available, require `webman-tech/laravel-redis` or bind `%s` into container',
                RedisFactory::class,
                RedisFactory::class,
            ));
        }
        return $factory->connection($this->config['redis_connection_name']);
    }

    /**
     * @inheritDoc
     */
    protected function tooManyAttempts(Limit $limit): bool
    {
        $limiter = new DurationLimiter(
            $this->getRedis(), $limit->key, $limit->maxAttempts, $limit->decaySeconds
        );

        $key = $limit->key;
        return tap(!$limiter->acquire(), function () use ($key, $limiter) {
            [$this->decaysAt[$key], $this->remaining[$key]] = [
                $limiter->decaysAt, $limiter->remaining,
            ];
        });
    }

    /**
     * @inheritDoc
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts, ?int $retryAfter = null): int
    {
        return is_null($retryAfter) ? $this->remaining[$key] : 0;
    }

    /**
     * @inheritDoc
     */
    protected function getTimeUntilNextRetry(string $key): int
    {
        return $this->decaysAt[$key] - $this->currentTime();
    }
}
