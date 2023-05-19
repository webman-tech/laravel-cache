<?php

namespace WebmanTech\LaravelCache\Mock;

use Illuminate\Cache\MemcachedConnector;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use support\Container;
use Webman\Container as WebmanContainer;
use WebmanTech\LaravelCache\CacheConfigRepository;

final class LaravelApp implements \ArrayAccess
{
    private $container;

    public function __construct()
    {
        $this->container = new WebmanContainer();
        $this->container->addDefinitions([
            'files' => function (): Filesystem {
                if ($component = $this->guessContainerComponent(['files'], Filesystem::class)) {
                    return $component;
                }
                if (!class_exists(Filesystem::class)) {
                    throw new \InvalidArgumentException('must install illuminate/filesystem first');
                }
                return new Filesystem();
            },
            'memcached.connector' => function () {
                if ($component = $this->guessContainerComponent(['memcached.connector'], MemcachedConnector::class)) {
                    return $component;
                }
                return new MemcachedConnector();
            },
            'redis' => function () {
                if ($component = $this->guessContainerComponent(['redis'], RedisFactory::class)) {
                    return $component;
                }
                return new WebmanRedisFactory();
            },
            'db' => function () {
                if ($component = $this->guessContainerComponent(['db'], ConnectionResolverInterface::class)) {
                    return $component;
                }
                return new WebmanDBConnectionResolver();
            },
            DispatcherContract::class => function () {
                if ($component = $this->guessContainerComponent(['events'], DispatcherContract::class)) {
                    return $component;
                }
                return new Dispatcher();
            },
            'config' => function () {
                return CacheConfigRepository::instance();
            }
        ]);
    }

    public function bound($abstract)
    {
        return Container::has($abstract);
    }

    private function guessContainerComponent(array $maybeContainerKeys, string $componentClass)
    {
        $maybeContainerKeys[] = $componentClass;
        foreach ($maybeContainerKeys as $name) {
            if (Container::has($name)) {
                $component = Container::get($name);
                if ($component instanceof $componentClass) {
                    return $component;
                }
            }
        }
        return null;
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