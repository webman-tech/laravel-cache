<?php

use Illuminate\Cache\FileStore;
use Illuminate\Cache\NullStore;

return [
    'enable' => true,
    'flush' => [
        /**
         * @see \WebmanTech\LaravelCache\Macro\FlushPreventMacro::$config
         */
        'prevent' => true,
        'ignore_store' => [FileStore::class, NullStore::class],
    ],
];
