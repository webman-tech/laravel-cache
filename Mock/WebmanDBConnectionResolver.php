<?php

namespace WebmanTech\LaravelCache\Mock;

use Illuminate\Database\ConnectionResolverInterface;
use support\Db;
use WebmanTech\LaravelCache\Helper\ConfigHelper;

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
        return ConfigHelper::getGlobal('database.default');
    }

    /**
     * @inheritDoc
     */
    public function setDefaultConnection($name)
    {
        throw new \InvalidArgumentException('Not support');
    }
}
