<?php

namespace WebmanTech\LaravelCache\Lock;

use Illuminate\Cache\MemcachedLock;

/**
 * 带 watchdog 自动续期的 Memcached 锁。
 *
 * @internal
 */
final class WatchdogMemcachedLock extends MemcachedLock
{
    use WatchdogLockTrait;

    public function __construct($memcached, $name, $seconds, $owner = null, float $renewRatio = 1 / 3)
    {
        parent::__construct($memcached, $name, $seconds, $owner);

        $this->initWatchdog($renewRatio);
    }

    /**
     * 从原始 MemcachedLock 创建 watchdog 锁。
     */
    public static function fromLock(MemcachedLock $lock, float $renewRatio = 1 / 3): self
    {
        return new self($lock->memcached, $lock->name, $lock->seconds, $lock->owner(), $renewRatio);
    }

    protected function renew(): bool
    {
        if (! $this->isOwnedByCurrentProcess()) {
            return false;
        }

        return $this->memcached->touch($this->name, $this->seconds);
    }
}
