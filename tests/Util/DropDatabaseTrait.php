<?php
namespace Mongolid\Laravel\Tests\Util;

use Mongolid\Connection\Connection;
use Mongolid\Container\Ioc;

trait DropDatabaseTrait
{
    public function dropDatabase()
    {
        $connection = Ioc::make(Connection::class);

        $connection->getRawConnection()
            ->dropDatabase($connection->defaultDatabase);
    }
}
