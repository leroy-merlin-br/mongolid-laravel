<?php

namespace MongolidLaravel;

use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;
use TestCase;

class MongolidServiceProviderTest extends TestCase
{
    public function testShouldBoot()
    {
        // Set
        $provider = new MongolidServiceProvider($this->app);
        config(['auth.driver' => 'mongoLid']);

        // Actions
        $provider->boot();
        $result = $this->app['auth']->getProvider();

        // Actions
        $this->assertInstanceOf(MongolidUserProvider::class, $result);
    }

    public function testShouldRegisterConnector()
    {
        // Set
        $provider = new MongolidServiceProvider($this->app);
        config(['database.mongodb.default.database' => 'databaseName']);

        // Actions
        $provider->registerConnector();

        $pool = Ioc::make(Pool::class);

        // Assertions
        $this->assertEquals('databaseName', $pool->getConnection()->defaultDatabase);
    }

    public function testShouldRegisterConnectorWithUsernameAndPassword()
    {
        // Set
        $provider = new MongolidServiceProvider($this->app);
        config([
            'database.mongodb.default.database' => 'databaseName',
            'database.mongodb.default.username' => 'us3r',
            'database.mongodb.default.password' => 'p455',
        ]);

        // Actions
        $provider->registerConnector();

        $pool = Ioc::make(Pool::class);

        // Assertions
        $this->assertEquals('databaseName', $pool->getConnection()->defaultDatabase);
    }
}
