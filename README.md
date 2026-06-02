# webman-tech/laravel-cache

> Split from [webman-tech/laravel-monorepo](https://github.com/webman-tech/laravel-monorepo)

适用于 webman 的 Laravel 缓存组件，基于 illuminate/cache 实现。

## 安装

```bash
composer require webman-tech/laravel-cache
```

## 简介

该组件将 Laravel 强大的缓存功能引入 webman 框架中，使开发者能够使用 Laravel 的缓存 API。

所有方法和配置与 Laravel 几乎一致，因此使用方式可完全参考 [Laravel Cache 文档](https://laravel.com/docs/cache)。

## 特殊使用说明

### 1. Facades 使用方式

在 webman 中使用以下 Facades 替代 Laravel 的对应 Facades：

- 使用 `WebmanTech\LaravelCache\Facades\Cache` 替代 `Illuminate\Support\Facades\Cache`
- 使用 `WebmanTech\LaravelCache\Facades\CacheRateLimiter` 替代 `Illuminate\Support\Facades\RateLimiter`
- 使用 `WebmanTech\LaravelCache\Facades\CacheLocker` 处理锁操作，支持 `watchdog` 前缀实现锁自动续期

### 2. 命令行支持

组件提供以下命令行工具：

```bash
# 删除缓存下的某个键
php webman cache:forget key_name

# 清空所有缓存（注意：此方法使用 Cache::flush 来清除）
php webman cache:clear
```

### 3. 扩展支持

在 `config/plugin/webman-tech/laravel-cache/cache.php` 中配置 `extend`：

```php
return [
    'extend' => function(\Illuminate\Cache\CacheManager $cache) {
        $cache->extend('mongo', function () use ($cache) {
           return $cache->repository(new MongoStore);
        });
    }
];
```

### 4. PSR 标准支持

```php
// PSR-16 简单缓存
$psr16 = Cache::psr16();

// PSR-6 缓存池（需要安装 symfony/cache）
$psr6 = Cache::psr6();
```

### 5. 限流中间件

组件实现了适用于 webman 路由的限流中间件：

```php
use Webman\Route;
use WebmanTech\LaravelCache\Middleware\ThrottleRequestsFactory;

Route::get('/api/users', [UserController::class, 'index'])
    ->middleware([
        new ThrottleRequestsFactory([
            'limiter_for' => 'api', // 需要在 rate_limiter.php 中配置
        ]),
    ]);
```

### 6. Watchdog Lock（锁自动续期）

Laravel 的 Cache Lock 有固定的 TTL，长任务执行期间锁可能过期。Watchdog Lock 在锁持有期间自动续期，防止锁意外丢失。

```php
// 通过 CacheLocker 使用 watchdog 锁（推荐）
$lock = CacheLocker::watchdogOrder('order_123', 30); // 30s TTL，每 10s 自动续期
$lock->get(function () {
    // 长时间任务，锁不会过期
    $this->processOrder();
});

// 手动 acquire/release 模式
$lock = CacheLocker::watchdogOrder('order_123', 30);
if ($lock->acquire()) {
    try {
        $this->processOrder();
    } finally {
        $lock->release();
    }
}
```

watchdog 前缀的使用方式与 `restore` 前缀一致，仅影响行为，不影响锁名：

```php
CacheLocker::order('key', 30);          // 普通锁，锁名: order:key
CacheLocker::watchdogOrder('key', 30);   // watchdog 锁，锁名: order:key（同一把锁）
```

支持所有 cache store 类型（Redis、Database、Memcached、File、Array），Redis 使用 Lua 脚本原子续期，Database 使用条件更新，其他
store 尽力而为。

## 注意事项

1. `Cache::flush()` 会清空存储器下的所有数据，而非仅当前应用的缓存
2. 可通过配置 `app.flush.prevent = true` 禁止使用 flush 方法
3. 缓存的默认过期时间为永久，需要手动设置过期时间
