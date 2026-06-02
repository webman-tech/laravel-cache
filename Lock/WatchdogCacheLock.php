<?php

namespace WebmanTech\LaravelCache\Lock;

use Illuminate\Cache\CacheLock;

/**
 * 带 watchdog 自动续期的 Cache 锁（File / APC / DynamoDB 等通用）。
 *
 * @internal
 */
final class WatchdogCacheLock extends CacheLock
{
    use WatchdogLockTrait;

    public function __construct($store, $name, $seconds, $owner = null, float $renewRatio = 1 / 3)
    {
        parent::__construct($store, $name, $seconds, $owner);

        $this->initWatchdog($renewRatio);
    }

    /**
     * 从原始 CacheLock 创建 watchdog 锁。
     */
    public static function fromLock(CacheLock $lock, float $renewRatio = 1 / 3): self
    {
        return new self($lock->store, $lock->name, $lock->seconds, $lock->owner(), $renewRatio);
    }

    protected function renew(): bool
    {
        if (! $this->isOwnedByCurrentProcess()) {
            return false;
        }

        if ($this->seconds > 0) {
            return $this->store->put($this->name, $this->owner, $this->seconds);
        }

        return true;
    }
}
