<?php

namespace WebmanTech\LaravelCache\Mock;

use Illuminate\Cache\MemcachedConnector;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use support\Container;
use Webman\Container as WebmanContainer;
use WebmanTech\LaravelCache\Helper\ConfigHelper;
use WebmanTech\LaravelCache\Helper\ExtComponentGetter;

/**
 * @internal
 */
final class LaravelApp implements \ArrayAccess
{
    private $container;

    public function __construct()
    {
        $this->container = new WebmanContainer();
        $this->container->addDefinitions([
            'files' => fn() => ExtComponentGetter::get(Filesystem::class, [
                'default' => fn() => new Filesystem()
            ]),
            'memcached.connector' => fn() => ExtComponentGetter::get(MemcachedConnector::class, [
                'default' => fn() => new MemcachedConnector()
            ]),
            'redis' => fn() => ExtComponentGetter::get(RedisFactory::class, [
                'default' => fn() => new WebmanRedisFactory()
            ]),
            'db' => fn() => ExtComponentGetter::get(ConnectionResolverInterface::class, [
                'default' => fn() => LaravelDb::getInstance()->getDatabaseManager()
            ]),
            DispatcherContract::class => fn() => ExtComponentGetter::get(DispatcherContract::class, [
                'default' => fn() => new Dispatcher()
            ]),
            'config' => fn() => ExtComponentGetter::get(ConfigContract::class, [
                'default' => fn() => new Repository([
                    'cache' => ConfigHelper::get('cache', []),
                ]),
            ]),
        ]);
    }

    public function bound($abstract)
    {
        return Container::has($abstract);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->container->has($offset);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->container->get($offset);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new \InvalidArgumentException('Not support');
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new \InvalidArgumentException('Not support');
    }
}
