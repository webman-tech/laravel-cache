<?php

namespace WebmanTech\LaravelCache\Lock;

use Illuminate\Cache\RedisLock;

/**
 * 带 watchdog 自动续期的 Redis 锁。
 *
 * @internal
 */
final class WatchdogRedisLock extends RedisLock
{
    use WatchdogLockTrait;

    /**
     * Lua 脚本：原子续期（校验 owner + EXPIRE）。
     */
    private const LUA_RENEW = <<<'LUA'
if redis.call("get", KEYS[1]) == ARGV[1] then
    return redis.call("expire", KEYS[1], ARGV[2])
else
    return 0
end
LUA;

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

    protected function renew(): bool
    {
        return (bool) $this->redis->eval(self::LUA_RENEW, 1, $this->name, $this->owner, $this->seconds); // @phpstan-ignore method.notFound
    }
}
