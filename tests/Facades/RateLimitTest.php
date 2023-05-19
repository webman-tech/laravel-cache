<?php

namespace WebmanTech\LaravelCache\Tests\Facades;

use PHPUnit\Framework\TestCase;
use WebmanTech\LaravelCache\Facades\RateLimiter;

/**
 * https://laravel.com/docs/10.x/rate-limiting
 */
class RateLimitTest extends TestCase
{
    public function testInstance()
    {
        $this->assertInstanceOf(\Illuminate\Cache\RateLimiter::class, RateLimiter::instance());
    }

    public function testAttempt()
    {
        $key = 'key';
        // 2秒内执行最多一次
        $executed = RateLimiter::attempt($key, 1, function () {
            // do
        }, 2);
        $this->assertTrue($executed);
        $executed = RateLimiter::attempt($key, 1, function () {
            // do
        }, 2);
        $this->assertFalse($executed);
        sleep(2);
        $executed = RateLimiter::attempt($key, 1, function () {
            // do
        }, 2);
        $this->assertTrue($executed);

        RateLimiter::clear($key);
    }

    public function testManuallyAttempt()
    {
        $key = 'key2';
        $executed = RateLimiter::tooManyAttempts($key, 1); // 检查是否超过限制
        $this->assertFalse($executed);
        $current = RateLimiter::hit($key, 3); // 增加次数，3秒内
        $this->assertEquals(1, $current);
        $current = RateLimiter::hit($key, 3); // 增加次数，3秒内
        $this->assertEquals(2, $current);
        $this->assertEquals(0, RateLimiter::remaining($key, 2)); // 剩余次数
        $this->assertEquals(0, RateLimiter::retriesLeft($key, 2)); // 同 remaining
        sleep(1);
        $seconds = RateLimiter::availableIn($key); // 剩余多少秒
        $this->assertGreaterThan(0, $seconds);
        $this->assertLessThan(3, $seconds);
        RateLimiter::clear($key); // 清空
        $this->assertEquals(2, RateLimiter::remaining($key, 2)); // 剩余次数
    }
}