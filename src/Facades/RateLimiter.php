<?php

namespace WebmanTech\LaravelCache\Facades;

use Illuminate\Cache\RateLimiter as LaravelRateLimiter;
use WebmanTech\LaravelCache\CacheConfigRepository;

/**
 * @method static \Illuminate\Cache\RateLimiter for (string $name, \Closure $callback)
 * @method static \Closure limiter(string $name)
 * @method static bool tooManyAttempts($key, $maxAttempts)
 * @method static int hit($key, $decaySeconds = 60)
 * @method static mixed attempts($key)
 * @method static mixed resetAttempts($key)
 * @method static int retriesLeft($key, $maxAttempts)
 * @method static void clear($key)
 * @method static int availableIn($key)
 * @method static bool attempt($key, $maxAttempts, \Closure $callback, $decaySeconds = 60)
 *
 * @see \Illuminate\Cache\RateLimiter
 */
class RateLimiter
{
    public const FOR_REQUEST = 'request';

    private static $_instance;

    /**
     * @return LaravelRateLimiter
     */
    public static function instance(): LaravelRateLimiter
    {
        if (!static::$_instance) {
            $config = CacheConfigRepository::instance();
            $cache = Cache::instance()->store(
                $config->get('rate_limiter.limiter',
                    $config->get('cache.limiter')
                )
            );
            $rateLimiter = new LaravelRateLimiter($cache);
            foreach ($config->get('rate_limiter.for', []) as $name => $callback) {
                $rateLimiter->for($name, $callback);
            }
            static::$_instance = $rateLimiter;
        }

        return static::$_instance;
    }

    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(...$arguments);
    }
}