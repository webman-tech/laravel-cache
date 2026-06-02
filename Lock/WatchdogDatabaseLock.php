<?php

namespace WebmanTech\LaravelCache\Lock;

use Illuminate\Cache\DatabaseLock;

/**
 * 带 watchdog 自动续期的 Database 锁。
 *
 * @internal
 */
final class WatchdogDatabaseLock extends DatabaseLock
{
    use WatchdogLockTrait;

    public function __construct($connection, $table, $name, $seconds, $owner = null, $lottery = [2, 100], $defaultTimeoutInSeconds = 86400, float $renewRatio = 1 / 3)
    {
        parent::__construct($connection, $table, $name, $seconds, $owner, $lottery, $defaultTimeoutInSeconds);

        $this->initWatchdog($renewRatio);
    }

    /**
     * 从原始 DatabaseLock 创建 watchdog 锁。
     */
    public static function fromLock(DatabaseLock $lock, float $renewRatio = 1 / 3): self
    {
        return new self(
            $lock->connection,
            $lock->table,
            $lock->name,
            $lock->seconds,
            $lock->owner(),
            $lock->lottery,
            $lock->defaultTimeoutInSeconds,
            $renewRatio,
        );
    }

    protected function renew(): bool
    {
        return $this->connection->table($this->table)
            ->where('key', $this->name)
            ->where('owner', $this->owner)
            ->update([
                'expiration' => $this->expiresAt(),
            ]) >= 1;
    }
}
