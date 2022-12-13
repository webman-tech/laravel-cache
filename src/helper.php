<?php

use WebmanTech\LaravelCache\Facades\Cache;

if (! function_exists('cache')) {
    /**
     * Get / set the specified cache value.
     *
     * If an array is passed, we'll assume you want to put to the cache.
     *
     * @param  dynamic  key|key,default|data,expiration|null
     * @return mixed|\Illuminate\Cache\CacheManager
     *
     * @throws \InvalidArgumentException
     */
    function cache()
    {
        $arguments = func_get_args();

        if (empty($arguments)) {
            return Cache::instance();
        }

        if (is_string($arguments[0])) {
            return Cache::get(...$arguments);
        }

        if (! is_array($arguments[0])) {
            throw new InvalidArgumentException(
                'When setting a value in the cache, you must pass an array of key / value pairs.'
            );
        }

        return Cache::put(key($arguments[0]), reset($arguments[0]), $arguments[1] ?? null);
    }
}