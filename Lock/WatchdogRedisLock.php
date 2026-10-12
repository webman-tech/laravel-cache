<?php

namespace WebmanTech\LaravelCache\Lock;

use Illuminate\Cache\LuaScripts;
use Illuminate\Cache\RedisLock;

/**
 * 带 watchdog 自动续期的 Redis 锁。
 *
 * @internal
 */
final class WatchdogRedisLock extends RedisLock
{
    use WatchdogLockTrait;

    public function __construct($redis, string $name, int $seconds, ?string $owner = null, float $renewRatio = 1 / 3)
    {
        parent::__construct($redis, $name, $seconds, $owner);

        $this->initWatchdog($renewRatio);
    }

    /**
     * 从原始 RedisLock 创建 watchdog 锁。
     */
    public static function fromLock(RedisLock $lock, float $renewRatio = 1 / 3): self
    {
        return new self($lock->redis, $lock->name, $lock->seconds, $lock->owner(), $renewRatio);
    }

    /**
     * 原子续期：复用 Illuminate 的 refreshLock 脚本，
     * 其 tonumber(ARGV[2]) > 0 分支处理了 seconds <= 0 的情况（转 persist 而非 expire 0）。
     */
    protected function renew(): bool
    {
        // @phpstan-ignore-next-line
        return (bool)$this->redis->eval(LuaScripts::refreshLock(), 1, $this->name, $this->owner, $this->seconds);
    }
}
