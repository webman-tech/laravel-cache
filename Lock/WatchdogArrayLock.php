<?php

namespace WebmanTech\LaravelCache\Lock;

use Illuminate\Cache\ArrayLock;
use Illuminate\Support\Carbon;

/**
 * 带 watchdog 自动续期的 Array 锁。
 *
 * @internal
 */
final class WatchdogArrayLock extends ArrayLock
{
    use WatchdogLockTrait;

    public function __construct($store, $name, $seconds, $owner = null, float $renewRatio = 1 / 3)
    {
        parent::__construct($store, $name, $seconds, $owner);

        $this->initWatchdog($renewRatio);
    }

    /**
     * 从原始 ArrayLock 创建 watchdog 锁。
     */
    public static function fromLock(ArrayLock $lock, float $renewRatio = 1 / 3): self
    {
        return new self($lock->store, $lock->name, $lock->seconds, $lock->owner(), $renewRatio);
    }

    protected function renew(): bool
    {
        if (! $this->exists() || ! $this->isOwnedByCurrentProcess()) {
            return false;
        }

        $this->store->locks[$this->name]['expiresAt'] = $this->seconds === 0
            ? null
            : Carbon::now()->addSeconds($this->seconds);

        return true;
    }
}
