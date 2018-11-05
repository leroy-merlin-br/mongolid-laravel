<?php
namespace Mongolid\Laravel\Tests\Util;

use Mongolid\Connection\Connection;
use Mongolid\Container\Ioc;
use Mongolid\Util\CacheComponent;
use Mongolid\Util\CacheComponentInterface;

trait SetupConnectionTrait
{
    public function setupConnection(string $host, string $database)
    {
        Ioc::singleton(
            Connection::class,
            function () use ($host, $database) {
                $connection = new Connection("mongodb://{$host}:27017/{$database}");
                $connection->defaultDatabase = $database;

                return $connection;
            }
        );

        Ioc::instance(
            CacheComponentInterface::class,
            new CacheComponent()
        );
    }
}
