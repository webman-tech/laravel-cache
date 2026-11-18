<?php

namespace WebmanTech\LaravelCache\Mock;

use Illuminate\Cache\MemcachedConnector;
use Illuminate\Container\Container as LaravelContainer;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use WebmanTech\LaravelCache\Helper\ExtComponentGetter;

/**
 * @internal
 * @implements \ArrayAccess<string, mixed>
 */
final class LaravelApp implements \ArrayAccess
{
    private LaravelContainer $container;

    public function __construct()
    {
        $this->container = new LaravelContainer();
        $bindings = [
            'files' => Filesystem::class,
            'memcached.connector' => MemcachedConnector::class,
            'redis' => RedisFactory::class,
            'db' => ConnectionResolverInterface::class,
            DispatcherContract::class => DispatcherContract::class,
            'config' => ConfigContract::class,
        ];
        foreach ($bindings as $abstract => $needClass) {
            $instance = ExtComponentGetter::get($needClass);
            $this->container->bind($abstract, fn() => $instance);
        }
    }

    public function bound(string $abstract): bool
    {
        return $this->container->bound($abstract);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->container->bound($offset);
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
