<?php

namespace WebmanTech\LaravelCache;

use Illuminate\Config\Repository;
use support\Container;

final class CacheConfigRepository extends Repository
{
    public function __construct()
    {
        parent::__construct([
            'cache' => config('plugin.webman-tech.laravel-cache.cache', []),
            'rate_limiter' => config('plugin.webman-tech.laravel-cache.rate_limiter', [])
        ]);
    }

    public static function instance(): self
    {
        return Container::get(self::class);
    }
}