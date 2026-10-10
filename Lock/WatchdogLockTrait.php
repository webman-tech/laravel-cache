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
        // 回调必须只持有 WeakReference：
        // 若闭包强引用 $this，事件循环的 timer 注册表会一直持有锁对象，
        // 对象永不销毁 → __destruct 不触发 → 协程退出后无人停表 → 锁被无限续期。
        $ref = \WeakReference::create($this);

        $this->timerId = $this->addTimer($this->renewInterval, static function () use ($ref) {
            $lock = $ref->get();

            if ($lock === null) {
                // 锁对象已被 GC（协程异常退出 / 客户端断开且未走 release()）。
                // 此时已无人能停表，只能让续期自然停止，锁回归自身 TTL 过期。
                return;
            }

            $lock->tickWatchdog();
        });
    }

    /**
     * 单次续期。由定时器回调触发。
     *
     * 内部必须吞掉所有异常：workerman 事件循环会把回调异常交给 Worker 的 errorHandler，
     * 而该 handler 绑定的是 stopAll(250) —— 一次 Redis 超时就会重启整个 worker 进程。
     */
    private function tickWatchdog(): void
    {
        try {
            $renewed = $this->renew();
        } catch (\Throwable $e) {
            $this->stopOnRenewFailure($e);
            return;
        }

        if (! $renewed) {
            $this->stopWatchdog();
        }
    }

    /**
     * 续期抛异常时的降级处理：停止续期，让锁按自身 TTL 自然过期，并记录日志便于排查。
     */
    protected function stopOnRenewFailure(\Throwable $e): void
    {
        try {
            $this->stopWatchdog();
        } catch (\Throwable) {
            // 停表失败不应掩盖原始异常
        }

        // 日志入口自身也可能抛异常（如运行环境未注册 Log 通道），
        // 此处同样必须吞掉：否则日志失败会把续期异常放大成 worker 重启。
        try {
            $this->logRenewFailure($e);
        } catch (\Throwable) {
            // 记录日志失败不应影响降级停表
        }
    }

    /**
     * 记录续期失败日志。
     *
     * 日志函数位于 WebmanTech\CommonUtils 命名空间（非常见的全局 logger），
     * 故不能用 function_exists('logger') 判断——那在 webman 下恒为 false。
     */
    protected function logRenewFailure(\Throwable $e): void
    {
        \WebmanTech\CommonUtils\logger()->warning('watchdog lock renew failed, falling back to TTL expiration', [
            'lock' => $this->name,
            'owner' => $this->owner,
            'seconds' => $this->seconds,
            'exception' => $e->getMessage(),
        ]);
    }

    protected function stopWatchdog(): void
    {
        if ($this->timerId !== null) {
            $this->delTimer($this->timerId);
            $this->timerId = null;
        }
    }

    /**
     * 协程异常退出 / 客户端断开时 release() 可能来不及执行。
     * 定时器回调只持 WeakReference，因此对象能正常进入析构 —— 此处兜底停表，
     * 让锁回归自身 TTL 自然过期，避免被一个已死协程无限续期。
     */
    public function __destruct()
    {
        try {
            $this->stopWatchdog();
        } catch (\Throwable) {
            // 析构阶段不得抛出，否则会掩盖原始异常
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
