<?php

namespace WebmanTech\LaravelCache\Facades;

use Illuminate\Cache\RateLimiter as LaravelRateLimiter;
use WebmanTech\LaravelCache\Helper\ConfigHelper;

/**
 * @method static \Illuminate\Cache\RateLimiter for(\BackedEnum|\UnitEnum|string $name, \Closure $callback)
 * @method static \Closure|null limiter(\BackedEnum|\UnitEnum|string $name)
 * @method static mixed attempt(string $key, int $maxAttempts, \Closure $callback, \DateTimeInterface|\DateInterval|int $decaySeconds = 60)
 * @method static bool tooManyAttempts(string $key, int $maxAttempts)
 * @method static int hit(string $key, \DateTimeInterface|\DateInterval|int $decaySeconds = 60)
 * @method static int increment(string $key, \DateTimeInterface|\DateInterval|int $decaySeconds = 60, int $amount = 1)
 * @method static int decrement(string $key, \DateTimeInterface|\DateInterval|int $decaySeconds = 60, int $amount = 1)
 * @method static mixed attempts(string $key)
 * @method static bool resetAttempts(string $key)
 * @method static int remaining(string $key, int $maxAttempts)
 * @method static int retriesLeft(string $key, int $maxAttempts)
 * @method static void clear(string $key)
 * @method static int availableIn(string $key)
 * @method static string cleanRateLimiterKey(string $key)
 *
 * @see \Illuminate\Cache\RateLimiter
 */
class CacheRateLimiter
{
    public const FOR_REQUEST = 'request';

    private static $_instance;

    /**
     * @return LaravelRateLimiter
     */
    public static function instance(): LaravelRateLimiter
    {
        if (!self::$_instance) {
            $rateLimiterConfig = array_merge([
                'limiter' => null,
                'for' => [],
            ], ConfigHelper::get('rate_limiter', []));
            $cache = Cache::instance()->store($rateLimiterConfig['limiter']);
            $rateLimiter = new LaravelRateLimiter($cache);
            foreach ($rateLimiterConfig['for'] as $name => $callback) {
                $rateLimiter->for($name, $callback);
            }
            self::$_instance = $rateLimiter;
        }

        return self::$_instance;
    }

    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(...$arguments);
    }
}
