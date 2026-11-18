<?php

namespace WebmanTech\LaravelCache\Middleware;

use Closure;
use WebmanTech\CommonUtils\Middleware\BaseMiddleware;
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Response;
use WebmanTech\LaravelCache\Helper\ConfigHelper;

class ThrottleRequestsFactory extends BaseMiddleware
{
    private array $config = [
        'use_redis' => false, // 是否使用 redis 模式，推荐
        'redis_connection_name' => null, // redis 模式下使用的 redis connection Name
        'limiter_for' => null, // RateLimiter 的 for 的 name
        'with_headers' => true, // 返回时是否携带 header 信息
    ];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge(
            $this->config,
            ConfigHelper::get('rate_limiter.throttle_requests', []),
            $config
        );
    }

    /**
     * @inheritDoc
     */
    public function processRequest(Request $request, Closure $handler): Response
    {
        $throttleRequest = $this->config['use_redis']
            ? new ThrottleRequestsWithRedis($this->config)
            : new ThrottleRequests($this->config);

        return $throttleRequest->processRequest($request, $handler);
    }
}
