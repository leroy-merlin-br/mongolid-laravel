<?php
namespace Mongolid\Laravel\Tests\Util;

use Mongolid\Connection\Connection;
use Mongolid\Container\Container;

trait DropDatabaseTrait
{
    public function dropDatabase()
    {
        $connection = Container::make(Connection::class);

        $connection->getClient()
            ->dropDatabase($connection->defaultDatabase);
    }
}
