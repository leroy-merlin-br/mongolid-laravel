<?php

namespace MongolidLaravel\Integration;

use Mongolid\Connection\Connection;
use MongolidLaravel\MongolidServiceProvider;
use MongolidLaravel\TestCase;

class IntegrationTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->dropDatabase();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.mongodb.default', [
            'cluster' => [
                'nodes' => [
                    'primary' => [
                        'host' => env('DB_HOST', 'db'),
                        'port' => 27017,
                    ],
                ],
            ],
            'database' => 'testing',
        ]);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getPackageProviders($app): array
    {
        return [
            MongolidServiceProvider::class,
        ];
    }

    private function dropDatabase(): void
    {
        /** @var Connection $connection */
        $connection = $this->app->make(Connection::class);

        $connection->getClient()->dropDatabase($connection->defaultDatabase);
    }
}
