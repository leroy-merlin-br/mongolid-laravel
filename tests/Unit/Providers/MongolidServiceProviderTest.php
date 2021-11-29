<?php
namespace Mongolid\Laravel\Providers;

use Illuminate\Queue\Failed\NullFailedJobProvider;
use Mongolid\Connection\Connection;
use Mongolid\Container\Container;
use Mongolid\Event\EventTriggerService;
use Mongolid\Laravel\TestCase;

class MongolidServiceProviderTest extends TestCase
{
    public function testShouldBoot(): void
    {
        // Set
        $provider = new MongolidServiceProvider($this->app);
        config(['auth.providers.users.driver' => 'mongolid']);

        // Actions
        $provider->boot();
        $result = $this->app['auth']->getProvider();
        $queueFailerResult = Container::make('queue.failer');

        // Actions
        $this->assertInstanceOf(MongolidUserProvider::class, $result);
        $this->assertInstanceOf(NullFailedJobProvider::class, $queueFailerResult);
    }

    public function testShouldBootUsingQueueFailer(): void
    {
        // Set
        $provider = new MongolidServiceProvider($this->app);
        config(['queue.failed.collection' => 'failed_jobs']);

        // Actions
        $provider->boot();
        $result = Container::make('queue.failer');

        // Actions
        $this->assertInstanceOf(FailedJobProvider::class, $result);
    }

    public function testShouldRegister(): void
    {
        // Set
        $provider = new MongolidServiceProvider($this->app);
        config(['database.mongodb.default.database' => 'databaseName']);

        // Actions
        $provider->register();

        $connection = Container::make(Connection::class);
        $eventService = Container::make(EventTriggerService::class);

        // Assertions
        $this->assertEquals('databaseName', $connection->defaultDatabase);
        $this->assertInstanceOf(EventTriggerService::class, $eventService);
    }

    /**
     * @dataProvider connectionVariations
     */
    public function testShouldRegisterConnector($config, $connectionString): void
    {
        // Set
        $provider = new MongolidServiceProvider($this->app);
        config(['database.mongodb.default' => $config]);

        // Actions
        $provider->registerConnector();

        $connection = Container::make(Connection::class);
        $mongoClient = $connection->getClient();

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
