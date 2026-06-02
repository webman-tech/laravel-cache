<?php

namespace WebmanTech\LaravelCache\Lock;

use Illuminate\Cache\ArrayLock;
use Illuminate\Cache\CacheLock;
use Illuminate\Cache\DatabaseLock;
use Illuminate\Cache\Lock as AbstractLock;
use Illuminate\Cache\MemcachedLock;
use Illuminate\Cache\RedisLock;
use Illuminate\Contracts\Cache\Lock;

/**
 * 带 watchdog 自动续期的锁。
 *
 * 继承 Lock 抽象类，内部根据 lock 类型自动创建对应的 WatchdogXxxLock。
 * 用户无需关心底层 store 类型，直接使用即可。
 *
 * @usage
 * // 通过 CacheLocker
 * CacheLocker::watchdogOrder('key', 30)->get($callback);
 *
 * // 直接使用
 * $watchdog = new WatchdogLock(Cache::lock('order', 30));
 * $watchdog->get($callback);
 * $watchdog->block(5, $callback);
 */
final class WatchdogLock extends AbstractLock
{
    private AbstractLock $inner;

    public function __construct(AbstractLock $lock, float $renewRatio = 1 / 3)
    {
        parent::__construct($lock->name, $lock->seconds, $lock->owner);

        $this->sleepMilliseconds = $lock->sleepMilliseconds;
        $this->inner = self::createInner($lock, $renewRatio);
    }

    /**
     * 从任意 Lock 创建 WatchdogLock。
     */
    public static function create(Lock $lock, float $renewRatio = 1 / 3): self
    {
        if ($lock instanceof AbstractLock) {
            return new self($lock, $renewRatio);
        }

        throw new \InvalidArgumentException(sprintf(
            'WatchdogLock requires an instance of [%s], [%s] given.',
            AbstractLock::class,
            get_class($lock),
        ));
    }

    public function acquire(): bool
    {
        return $this->inner->acquire();
    }

    public function release(): bool
    {
        return $this->inner->release();
    }

    public function forceRelease(): void
    {
        $this->inner->forceRelease();
    }

    public function get($callback = null): mixed
    {
        return $this->inner->get($callback);
    }

    public function block($seconds, $callback = null): mixed
    {
        return $this->inner->block($seconds, $callback);
    }

    protected function getCurrentOwner(): string
    {
        return $this->inner->getCurrentOwner();
    }

    private static function createInner(AbstractLock $lock, float $renewRatio): AbstractLock
    {
        return match (true) {
            $lock instanceof RedisLock => WatchdogRedisLock::fromLock($lock, $renewRatio),
            $lock instanceof DatabaseLock => WatchdogDatabaseLock::fromLock($lock, $renewRatio),
            $lock instanceof MemcachedLock => WatchdogMemcachedLock::fromLock($lock, $renewRatio),
            $lock instanceof CacheLock => WatchdogCacheLock::fromLock($lock, $renewRatio),
            $lock instanceof ArrayLock => WatchdogArrayLock::fromLock($lock, $renewRatio),
            default => $lock,
        };
    }
}
