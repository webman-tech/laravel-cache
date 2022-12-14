<?php

use Illuminate\Cache\RateLimiting\Limit;
use Webman\Http\Request;
use WebmanTech\LaravelCache\Facades\RateLimiter as RateLimiterFacade;

return [
    /**
     * RateLimiter 使用的默认驱动
     * 为 null 时同 cache 下的 default
     */
    'limiter' => null,
    /**
     * RateLimiter::for 的快速配置
     */
    'for' => [
        RateLimiterFacade::FOR_REQUEST => function (Request $request) {
            return Limit::perMinute(1000);
        }
    ],
    /**
     * ThrottleRequestsFactory 的配置
     */
    'throttle_requests' => [
        'use_redis' => false,
        'redis_connection_name' => null,
        'limiter_for' => null,
    ]
];