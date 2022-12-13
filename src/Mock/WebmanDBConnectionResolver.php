<?php

namespace WebmanTech\LaravelCache\Mock;

use Illuminate\Database\ConnectionResolverInterface;
use support\Db;

final class WebmanDBConnectionResolver implements ConnectionResolverInterface
{
    /**
     * @inheritDoc
     */
    public function connection($name = null)
    {
        return Db::connection($name);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultConnection()
    {
        return config('database.default');
    }

    /**
     * @inheritDoc
     */
    public function setDefaultConnection($name)
    {
        throw new \InvalidArgumentException('Not support');
    }
}