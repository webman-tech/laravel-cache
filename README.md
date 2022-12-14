# webman-tech/laravel-cache

Laravel [illuminate/cache](https://packagist.org/packages/illuminate/cache) for webman

## 介绍

站在巨人（laravel）的肩膀上使缓存使用更加*可靠*和*便捷*

所有方法和配置与 laravel 几乎一模一样，因此使用方式完全参考 [Laravel文档](https://laravel.com/docs/8.x/cache) 即可

同步支持 [RateLimiter](https://laravel.com/docs/8.x/rate-limiting)

## 安装

```bash
composer require webman-tech/laravel-cache
```

## 使用

所有 API 同 laravel，以下仅对有些特殊的操作做说明

### Facade 入口

使用 `WebmanTech\LaravelCache\Facades\Cache` 代替 `Illuminate\Support\Facades\Cache`

使用 `WebmanTech\LaravelCache\Facades\RateLimiter` 代替 `Illuminate\Support\Facades\RateLimiter`

### command 支持

- `php webman cache:forget xxx`: 删除缓存下的某个键

- `php webman cache:clear`: 清空所有缓存 （！！注意：此方法使用 Cache::flush 来清除，影响范围见下文中的使用注意事项！！）

### extend 支持

在 `config/plugin/webman-tech/laravel-cache/cache.php` 中配置 `extend`

```PHP
<?php

return [
    'extend' => function(\Illuminate\Cache\CacheManager $cache) {
        $cache->extend('mongo', function () use ($cache) {
           return $cache->repository(new MongoStore);
        });
    }
];
```

### PSR6 和 PSR16 支持

- PSR16: `Cache::psr16()`

- PSR6: `Cache::psr6()`，需要先安装依赖 `symfony/cache`

### Throttle Middleware 支持

该库实现了类似 Laravel Route 下的 [throttle(Middleware\ThrottleRequests)](https://laravel.com/docs/8.x/routing#rate-limiting)，
适用于 webman 路由，用于快速处理接口限流等

#### 配置

在 `config/plugin/webman-tech/laravel-cache/rate_limiter.php` 下配置 `for`，
配置方式同 Laravel 的 `RateLimiter::for`

#### 使用

```php
<?php
use Webman\Route;

Route::get('/example', function () {
    //
})->middleware([
    \WebmanTech\LaravelCache\Middleware\ThrottleRequestsFactory::class,
]);

Route::get('/example2', function () {
    //
})->middleware([
    new \WebmanTech\LaravelCache\Middleware\ThrottleRequestsFactory([
        'limiter_for' => 'upload', // 需要在 rate_limiter.php 中配置 upload 的 for
    ]),
]);
```

## 使用注意事项

<details>
<summary>关于默认的缓存过期时间</summary>

Laravel Cache 没有缓存的默认过期时间

Cache::put 方法的第三个参数 ttl，不传时为永久缓存，为 0 或负数时表示移除该缓存（等同于 forget）
</details>

<details>
<summary>Cache::flush 清楚的范围</summary>

会清空该存储器下的所有数据，而非指定的 prefix 下的，所以当缓存共享，通过 prefix 区分时，需要谨慎调用该方法
</details>