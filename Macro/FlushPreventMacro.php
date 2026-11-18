<?php

namespace WebmanTech\LaravelCache\Macro;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use WebmanTech\LaravelCache\Exceptions\PreventFlushException;

/**
 * 用于项目中禁用 flush 方法
 */
final class FlushPreventMacro
{
    private array $config = [
        'prevent' => false,
        'ignore_store' => [NullStore::class, ArrayStore::class, FileStore::class],
    ];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    public function macro(): void
    {
        if (!$this->config['prevent']) {
            return;
        }

        $ignoreStores = $this->config['ignore_store'];
        Repository::macro('flush', function () use ($ignoreStores) {
            /** @var Repository $this */
            $store = $this->getStore();
            $prevent = true;
            foreach ($ignoreStores as $ignoreStore) {
                if ($store instanceof $ignoreStore) {
                    $prevent = false;
                    break;
                }
            }
            if (!$prevent) {
                return $this->getStore()->flush();
            }
            throw new PreventFlushException();
        });
    }
}
