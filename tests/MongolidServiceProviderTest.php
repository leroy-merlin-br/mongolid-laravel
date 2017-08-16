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

    /**
     * @dataProvider connectionVariations
     */
    public function testShouldRegisterConnector($config, $connectionString)
    {
        // Set
        $provider = new MongolidServiceProvider($this->app);
        config(['database.mongodb.default' => $config]);

        // Actions
        $provider->registerConnector();

        $pool = Ioc::make(Pool::class);
        $mongoClient = $pool->getConnection()->getRawConnection();

        // Assertions
        $this->assertEquals($connectionString, (string) $mongoClient);
    }

    public function connectionVariations()
    {
        return [
            'default values' => [
                'config' => [],
                'connectionString' => 'mongodb://127.0.0.1:27017/mongolid',
            ],
            'custom host and port' => [
                'config' => [
                    'host' => 'localhost',
                    'port' => 27917,
                ],
                'connectionString' => 'mongodb://localhost:27917/mongolid',
            ],
            'username and password' => [
                'config' => [
                    'database' => 'databaseName',
                    'username' => 'us3r',
                    'password' => 'p455',
                ],
                'connectionString' => 'mongodb://us3r:p455@127.0.0.1:27017/databaseName',
            ],
            'cluster connection with replica set' => [
                'config' => [
                    'cluster' => [
                        'replica_set' => 'rs-ds123',
                        'nodes' => [
                            'primary' => [
                                'host' => 'host-a',
                                'port' => 27017,
                            ],
                            'secondary' => [
                                'host' => 'host-b',
                                'port' => 27917,
                            ],
                        ],
                    ],
                ],
                'connectionString' => 'mongodb://host-a:27017,host-b:27917/mongolid?replicaSet=rs-ds123',
            ],
            'shared cluster (without replica set)' => [
                'config' => [
                    'cluster' => [
                        'nodes' => [
                            'primary' => [
                                'host' => 'host-a',
                                'port' => 27017,
                            ],
                            'secondary' => [
                                'host' => 'host-b',
                                'port' => 27917,
                            ],
                        ],
                    ],
                    'database' => 'database',
                ],
                'connectionString' => 'mongodb://host-a:27017,host-b:27917/database',
            ],
            'connection string overwrite all' => [
                'config' => [
                    'database' => 'databaseName',
                    'username' => 'us3r',
                    'password' => 'p455',
                    'connection_string' => 'mongodb://user:pass@localhost:27017/my_db',
                ],
                'connectionString' => 'mongodb://user:pass@localhost:27017/my_db',
            ],
        ];
    }
}
