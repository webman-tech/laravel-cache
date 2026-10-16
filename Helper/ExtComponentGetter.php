<?php

namespace WebmanTech\LaravelCache\Helper;

use Illuminate\Cache\MemcachedConnector;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use WebmanTech\LaravelDatabase\Facades\DB as DatabaseFacade;
use WebmanTech\LaravelRedis\Facades\Redis as RedisFacade;

/**
 * @internal
 */
final class ExtComponentGetter extends BaseExtComponentGetter
{
    protected static function getDefine(): array
    {
        return [
            Filesystem::class => [
                'alias' => ['files'],
                'singleton' => fn() => new Filesystem(),
            ],
            MemcachedConnector::class => [
                'singleton' => fn() => new MemcachedConnector(),
            ],
            /** @see \Illuminate\Cache\RedisStore */
            RedisFactory::class => [
                'alias' => ['redis'],
                // laravel-redis 为可选依赖，未安装时返回 null（redis store 使用时才需要）
                'singleton' => fn() => class_exists(RedisFacade::class) ? RedisFacade::resolveDefault() : null,
            ],
            /** @see \Illuminate\Cache\DatabaseStore */
            ConnectionResolverInterface::class => [
                'alias' => ['db'],
                // laravel-database 为可选依赖，未安装时返回 null（database store 使用时才需要）
                'singleton' => fn() => class_exists(DatabaseFacade::class) ? DatabaseFacade::getInstance()->getDatabaseManager() : null,
            ],
            DispatcherContract::class => [
                'alias' => ['events'],
                'singleton' => fn() => new Dispatcher(),
            ],
            ConfigContract::class => [
                // 不提供 config alias：宿主容器的 config 多为全量配置（如 webman 惯例的 plugin.* 键），缺少 cache.* 结构，
                // 直接绑定会导致 CacheManager 读不到缓存配置；此处需要的是注入 cache 配置后的派生 Repository
                'singleton' => fn() => new Repository([
                    'cache' => ConfigHelper::get('cache', []),
                ]),
            ],
        ];
    }
}
