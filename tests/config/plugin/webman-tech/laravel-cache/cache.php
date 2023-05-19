<?php

use Illuminate\Support\Str;

/**
 * @link https://github.com/laravel/laravel/blob/8.x/config/cache.php
 */
return [
    'default' => getenv('CACHE_DRIVER') ?: 'file',
    'stores' => [
        'apc' => [
            'driver' => 'apc',
        ],
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'lock_connection' => null,
        ],
        'file' => [
            'driver' => 'file',
            'path' => runtime_path('cache'),
        ],
        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => getenv('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                getenv('MEMCACHED_USERNAME'),
                getenv('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => getenv('MEMCACHED_HOST') ?: '127.0.0.1',
                    'port' => getenv('MEMCACHED_PORT') ?: 11211,
                    'weight' => 100,
                ],
            ],
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],
        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => getenv('AWS_ACCESS_KEY_ID'),
            'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
            'region' => getenv('AWS_DEFAULT_REGION') ?: 'us-east-1',
            'table' => getenv('DYNAMODB_CACHE_TABLE') ?: 'cache',
            'endpoint' => getenv('DYNAMODB_ENDPOINT'),
        ],
        'octane' => [
            'driver' => 'octane',
        ],
    ],
    'prefix' => getenv('CACHE_PREFIX', Str::slug(config('app.name', 'webman'), '_').'_cache'),
    'extend' => null,
];