<?php

namespace WebmanTech\LaravelCache\Lock;

use WebmanTech\CommonUtils\Timer;

/**
 * Watchdog 锁通用能力。
 *
 * watchdog 启停绑定在 acquire/release 上，因此无论通过 callback 模式还是手动模式，
 * 锁持有期间都会自动续期。
 *
 * @internal
 */
trait WatchdogLockTrait
{
    protected ?int $timerId = null;

    protected int $renewInterval;

    /**
     * 初始化续期间隔。
     */
    protected function initWatchdog(float $renewRatio = 1 / 3): void
    {
        $this->renewInterval = max(1, (int) ($this->seconds * $renewRatio));
    }

    /**
     * 获取锁，成功后自动启动 watchdog。
     */
    public function acquire(): bool
    {
        $result = parent::acquire();

        if ($result) {
            $this->startWatchdog();
        }

        return $result;
    }

    /**
     * 释放锁，同时停止 watchdog。
     */
    public function release(): bool
    {
        $this->stopWatchdog();

        return parent::release();
    }

    /**
     * 强制释放锁，同时停止 watchdog。
     */
    public function forceRelease(): void
    {
        $this->stopWatchdog();

        parent::forceRelease();
    }

    protected function startWatchdog(): void
    {
        $this->timerId = $this->addTimer($this->renewInterval, function () {
            if (! $this->renew()) {
                $this->stopWatchdog();
            }
        });
    }

    protected function stopWatchdog(): void
    {
        if ($this->timerId !== null) {
            $this->delTimer($this->timerId);
            $this->timerId = null;
        }
    }

    protected function addTimer(int $interval, callable $callback): int
    {
        return Timer::add($interval, $callback);
    }

    protected function delTimer(int $timerId): void
    {
        Timer::del($timerId);
    }

    /**
     * 原子续期。子类必须实现。
     */
    abstract protected function renew(): bool;
}
