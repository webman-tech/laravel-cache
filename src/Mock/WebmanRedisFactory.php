<?php

namespace WebmanTech\LaravelCache\Mock;

use Illuminate\Contracts\Redis\Factory;
use support\Redis;

final class WebmanRedisFactory implements Factory
{
    /**
     * @inheritDoc
     */
    public function connection($name = null)
    {
        return Redis::connection($name);
    }
}