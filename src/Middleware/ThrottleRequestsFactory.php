<?php

namespace WebmanTech\LaravelCache\Middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use WebmanTech\LaravelCache\CacheConfigRepository;

class ThrottleRequestsFactory implements MiddlewareInterface
{
    private $config = [
        'use_redis' => false, // 是否使用 redis 模式，推荐
        'redis_connection_name' => null, // redis 模式下使用的 redis connection Name
        'limiter_for' => null, // RateLimiter 的 for 的 name
        'with_headers' => true, // 返回时是否携带 header 信息
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge(
            $this->config,
            CacheConfigRepository::instance()->get('rate_limiter.throttle_requests', []),
            $config
        );
    }

    /**
     * @inheritDoc
     */
    public function process(Request $request, callable $handler): Response
    {
        if ($this->config['use_redis']) {
            $throttleRequest = new ThrottleRequestsWithRedis($this->config);
        } else {
            $throttleRequest = new ThrottleRequests($this->config);
        }

        return $throttleRequest->process($request, $handler);
    }
}