<?php

namespace WebmanTech\LaravelCache;

use Illuminate\Config\Repository;
use support\Container;
use WebmanTech\LaravelCache\Helper\ConfigHelper;

/**
 * @internal
 */
final class CacheConfigRepository extends Repository
{
    public function __construct()
    {
        parent::__construct([
            'cache' => ConfigHelper::get('cache', []),
            'rate_limiter' => ConfigHelper::get('rate_limiter', [])
        ]);
    }

    public static function instance(): self
    {
        return Container::get(self::class);
    }
}
