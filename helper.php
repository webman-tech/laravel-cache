<?php

use Illuminate\Support\Facades\Date;
use WebmanTech\LaravelCache\Facades\Cache;

if (! function_exists('cache')) {
    /**
     * Get / set the specified cache value.
     *
     * If an array is passed, we'll assume you want to put to the cache.
     *
     * @param  string|array<string, mixed>|null  $key  key|data
     * @param  mixed  $default  default|expiration|null
     * @return ($key is null ? \Illuminate\Cache\CacheManager : ($key is string ? mixed : bool))
     *
     * @throws \InvalidArgumentException
     */
    function cache($key = null, $default = null)
    {
        if (is_null($key)) {
            return Cache::instance();
        }

        if (is_string($key)) {
            return Cache::instance()->get($key, $default);
        }

        if (! is_array($key)) {
            throw new InvalidArgumentException(
                'When setting a value in the cache, you must pass an array of key / value pairs.'
            );
        }

        return Cache::instance()->put(key($key), reset($key), ttl: $default);
    }
}

if (! function_exists('now')) {
    /**
     * Create a new Carbon instance for the current time.
     *
     * Cache\Lock::block 中有使用到
     *
     * @param  \DateTimeZone|string|null  $tz
     * @return \Illuminate\Support\Carbon
     */
    function now($tz = null)
    {
        return Date::now($tz);
    }
}
