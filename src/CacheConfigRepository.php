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
        ]);
    }

    public static function instance(): self
    {
        return Container::get(self::class);
    }
}