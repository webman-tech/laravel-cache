<?php

namespace WebmanTech\LaravelCache\Helper;

use WebmanTech\CommonUtils\Container;

/**
 * @internal
 */
final class ExtComponentGetter
{
    /**
     * @template T
     * @param class-string<T> $needClass
     * @param array $pick
     * @return T
     */
    public static function get(string $needClass, array $pick = [])
    {
        $component = null;
        if (Container::has($needClass)) {
            $component = Container::get($needClass);
        }
        if (!$component && $pick) {
            foreach ($pick as $className => $getInstance) {
                if ($className === 'default' || class_exists($className)) {
                    $component = $getInstance();
                    break;
                }
            }
        }
        if (!$component instanceof $needClass) {
            throw new \RuntimeException($needClass . ' is not exist');
        }

        return $component;
    }
}
