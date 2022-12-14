<?php

namespace WebmanTech\LaravelCache\Facades;

use Illuminate\Cache\CacheManager;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use support\Container;
use Symfony\Component\Cache\Adapter\Psr16Adapter;
use WebmanTech\LaravelCache\CacheConfigRepository;
use WebmanTech\LaravelCache\Mock\LaravelApp;

/**
 * @method static \Illuminate\Cache\TaggedCache tags(array|mixed $names)
 * @method static \Illuminate\Contracts\Cache\Lock lock(string $name, int $seconds = 0, mixed $owner = null)
 * @method static \Illuminate\Contracts\Cache\Lock restoreLock(string $name, string $owner)
 * @method static \Illuminate\Contracts\Cache\Repository  store(string|null $name = null)
 * @method static \Illuminate\Contracts\Cache\Store getStore()
 * @method static bool add(string $key, $value, \DateTimeInterface|\DateInterval|int $ttl = null)
 * @method static bool flush()
 * @method static bool forever(string $key, $value)
 * @method static bool forget(string $key)
 * @method static bool has(string $key)
 * @method static bool missing(string $key)
 * @method static bool put(string $key, $value, \DateTimeInterface|\DateInterval|int $ttl = null)
 * @method static int|bool decrement(string $key, $value = 1)
 * @method static int|bool increment(string $key, $value = 1)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static mixed remember(string $key, \DateTimeInterface|\DateInterval|int $ttl, \Closure $callback)
 * @method static mixed rememberForever(string $key, \Closure $callback)
 * @method static mixed sear(string $key, \Closure $callback)
 *
 * @see \Illuminate\Cache\CacheManager
 * @see \Illuminate\Cache\Repository
 */
class Cache
{
    private static $_instance;

    /**
     * @return CacheManager
     */
    public static function instance(): CacheManager
    {
        if (!static::$_instance) {
            $cacheManager = new CacheManager(Container::get(LaravelApp::class));
            if ($extend = CacheConfigRepository::instance()->get('cache.extend')) {
                call_user_func($extend, $cacheManager);
            }
            static::$_instance = $cacheManager;
        }

        return static::$_instance;
    }

    /**
     * PSR16 实例
     * @return CacheInterface
     */
    public static function psr16(): CacheInterface
    {
        return static::instance()->store();
    }

    /**
     * PSR6 实例
     * @return CacheItemPoolInterface
     */
    public static function psr6(): CacheItemPoolInterface
    {
        return new Psr16Adapter(static::psr16());
    }

    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(...$arguments);
    }
}