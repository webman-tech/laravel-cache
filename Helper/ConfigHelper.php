<?php

namespace WebmanTech\LaravelCache\Helper;

/**
 * @internal
 */
class ConfigHelper
{
    /**
     * 获取配置
     * @param string $key
     * @param $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return config("plugin.webman-tech.laravel-cache.{$key}", $default);
    }
}
