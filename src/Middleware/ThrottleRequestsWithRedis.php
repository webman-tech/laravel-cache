<?php

namespace WebmanTech\LaravelCache\Middleware;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Redis\Connections\Connection as Redis;
use Illuminate\Redis\Limiters\DurationLimiter;

/**
 * 参考：https://github.com/laravel/framework/blob/9.x/src/Illuminate/Routing/Middleware/ThrottleRequestsWithRedis.php
 */
class ThrottleRequestsWithRedis extends ThrottleRequests
{
    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @var array
     */
    public $decaysAt = [];

    /**
     * @var array
     */
    public $remaining = [];

    public function __construct(string $limiterName = null, string $redisName = null)
    {
        parent::__construct($limiterName);

        $this->redis = \support\Redis::instance()->connection($redisName);
    }

    /**
     * @inheritDoc
     */
    protected function tooManyAttempts(Limit $limit): bool
    {
        $limiter = new DurationLimiter(
            $this->redis, $limit->key, $limit->maxAttempts, $limit->decayMinutes * 60
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
