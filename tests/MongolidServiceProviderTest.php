<?php

namespace MongolidLaravel;

use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;
use Mongolid\Event\EventTriggerService;
use Mongolid\Util\CacheComponentInterface;
use TestCase;

class MongolidServiceProviderTest extends TestCase
{
    public function testShouldBoot()
    {
        // Set
        $provider = new MongolidServiceProvider($this->app);
        config(['auth.providers.users.driver' => 'mongolid']);

        // Actions
        $provider->boot();
        $result = $this->app['auth']->getProvider();

        // Actions
        $this->assertInstanceOf(MongolidUserProvider::class, $result);
    }

    public function testShouldRegister()
    {
        // Set
        $provider = new MongolidServiceProvider($this->app);
        config(['database.mongodb.default.database' => 'databaseName']);

        // Actions
        $provider->register();

        $pool = Ioc::make(Pool::class);
        $eventService = Ioc::make(EventTriggerService::class);
        $cacheComponent = Ioc::make(CacheComponentInterface::class);

        // Assertions
        $this->assertEquals('databaseName', $pool->getConnection()->defaultDatabase);
        $this->assertInstanceOf(EventTriggerService::class, $eventService);
        $this->assertInstanceOf(LaravelCacheComponent::class, $cacheComponent);
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
