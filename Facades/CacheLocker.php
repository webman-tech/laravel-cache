<?php

namespace WebmanTech\LaravelCache\Facades;

use Illuminate\Cache\Lock as AbstractLock;
use Illuminate\Contracts\Cache\Lock;
use WebmanTech\LaravelCache\Lock\WatchdogLock;

/**
 * @see https://laravel.com/docs/cache#managing-locks
 *
 * @method static Lock test(?string $key = null, int $seconds = 0)
 * @method static Lock restoreTest(?string $key, string $owner)
 * @method static WatchdogLock watchdogTest(?string $key = null, int $seconds = 0)
 */
class CacheLocker
{
    public static function __callStatic($name, $arguments): mixed
    {
        if (str_starts_with($name, 'restore')) {
            $name = lcfirst(substr($name, strlen('restore')));
            $key = $arguments[0] ?? '';
            $owner = $arguments[1];

            return Cache::restoreLock(self::getLockName([$name, $key]), $owner);
        }

        $isWatchdog = str_starts_with($name, 'watchdog');
        if ($isWatchdog) {
            $name = lcfirst(substr($name, strlen('watchdog')));
        }
        $key = $arguments[0] ?? '';
        $seconds = (int)($arguments[1] ?? 0);

        $lock = Cache::lock(self::getLockName([$name, $key]), $seconds);

        if ($isWatchdog) {
            if (!$lock instanceof AbstractLock) {
                throw new \InvalidArgumentException('watchdog only support Illuminate\Cache\Lock');
            }
            $lock = new WatchdogLock($lock);
        }

        return $lock;
    }

    private static function getLockName(array $keys): string
    {
        return implode(':', $keys);
    }
}
