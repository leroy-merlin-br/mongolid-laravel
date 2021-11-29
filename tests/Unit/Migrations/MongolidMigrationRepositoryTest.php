<?php
namespace Mongolid\Laravel\Migrations;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Mockery as m;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\Laravel\TestCase;
use SplFixedArray;
use stdClass;

class MongolidMigrationRepositoryTest extends TestCase
{
    public function testGetRanMigrationsListMigrationsByPackage(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $repository = new MongolidMigrationRepository($connection, 'migrations');
        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('mongolid', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->find(
                [],
                [
                    'sort' => ['batch' => 1, 'migration' => 1],
                    'projection' => ['_id' => 0, 'migration' => 1],
                ]
            )
            ->andReturn([(object) ['migration' => 'bar']]);

        // Actions
        $result = $repository->getRan();

        // Assertions
        $this->assertSame(['bar'], $result);
    }

    public function testGetMigrationsList(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $repository = new MongolidMigrationRepository($connection, 'migrations');

        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        $steps = 10;
        $list = [(object) ['migration' => 'bar']];

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('mongolid', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->find(
                ['batch' => ['$gte' => 1]],
                [
                    'sort' => ['batch' => -1, 'migration' => -1],
                    'limit' => $steps,
                ]
            )
            ->andReturn(SplFixedArray::fromArray($list));

        // Actions
        $result = $repository->getMigrations($steps);

        // Assertions
        $this->assertSame($list, $result);
    }

    public function testGetLastMigrationsGetsAllMigrationsWithTheLatestBatchNumber(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $repository = new MongolidMigrationRepository($connection, 'migrations');

        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        $batchNumber = 19;
        $migration1 = new stdClass();
        $migration1->_id = new ObjectId();
        $migration1->batch = $batchNumber;
        $migration1->migration = 'create_users_index';

        $migration2 = new stdClass();
        $migration2->_id = new ObjectId();
        $migration2->batch = $batchNumber;
        $migration2->migration = 'create_products_index';

        $migrations = [$migration1, $migration2];
        $cursor = SplFixedArray::fromArray($migrations);

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('mongolid', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->find(
                [],
                [
                    'projection' => ['_id' => 0, 'batch' => 1],
                    'sort' => ['batch' => -1],
                    'limit' => 1,
                ]
            )
            ->andReturn(SplFixedArray::fromArray([(object) ['batch' => $batchNumber]]));

        $collection->expects()
            ->find(
                ['batch' => $batchNumber],
                ['sort' => ['migration' => -1]]
            )
            ->andReturn($cursor);

        // Actions
        $result = $repository->getLast();

        // Assertions
        $this->assertSame($migrations, $result);
    }

    public function testGetMigrationBatches(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $repository = new MongolidMigrationRepository($connection, 'migrations');

        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        $list = [
            (object) ['_id' => new ObjectId(), 'batch' => 1, 'migration' => 'create_user_indexes'],
            (object) ['_id' => new ObjectId(), 'batch' => 2, 'migration' => 'create_user_email_indexes'],
            (object) ['_id' => new ObjectId(), 'batch' => 2, 'migration' => 'drop_old_indexes'],
        ];

        $expected = [
            'create_user_indexes' => 1,
            'create_user_email_indexes' => 2,
            'drop_old_indexes' => 2,
        ];

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('mongolid', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->find(
                [],
                [
                    'sort' => ['batch' => 1, 'migration' => 1],
                ]
            )
            ->andReturn(SplFixedArray::fromArray($list));

        // Actions
        $result = $repository->getMigrationBatches();

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testLogMethodInsertsRecordIntoMigrationCollection(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $repository = new MongolidMigrationRepository($connection, 'migrations');

        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('mongolid', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->insertOne(['migration' => 'bar', 'batch' => 1]);

        // Actions
        $repository->log('bar', 1);
    }

    public function testDeleteMethodRemovesAMigrationFromTheCollection(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $repository = new MongolidMigrationRepository($connection, 'migrations');
        $migration = (object) ['migration' => 'create_foo_index'];

        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('mongolid', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->deleteOne(['migration' => 'create_foo_index']);

        // Actions
        $repository->delete($migration);
    }

    public function testGetNextBatchNumberReturnsLastBatchNumberPlusOne(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $repository = new MongolidMigrationRepository($connection, 'migrations');

        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        $batchNumber = 4;

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('mongolid', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->find(
                [],
                [
                    'projection' => ['_id' => 0, 'batch' => 1],
                    'sort' => ['batch' => -1],
                    'limit' => 1,
                ]
            )
            ->andReturn(SplFixedArray::fromArray([(object) ['batch' => $batchNumber]]));

        // Actions
        $result = $repository->getNextBatchNumber();

        // Assertions
        $this->assertSame(5, $result);
    }

    public function testGetLastBatchNumberReturnsMaxBatch(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $repository = new MongolidMigrationRepository($connection, 'migrations');

        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        $batchNumber = 4;

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('testing', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->find(
                [],
                [
                    'projection' => ['_id' => 0, 'batch' => 1],
                    'sort' => ['batch' => -1],
                    'limit' => 1,
                ]
            )
            ->andReturn(SplFixedArray::fromArray([(object) ['batch' => $batchNumber]]));

        // Actions
        $repository->setSource('testing');
        $result = $repository->getLastBatchNumber();

        // Assertions
        $this->assertSame($batchNumber, $result);
    }

    public function testCreateRepositoryCreatesProperDatabaseCollection(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $repository = new MongolidMigrationRepository($connection, 'migrations');

        // Expectations
        $connection->expects()
            ->getClient()
            ->never();

        // Actions
        $repository->createRepository();
    }

    public function testRepositoryExists(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $repository = new MongolidMigrationRepository($connection, 'migrations');

        // Expectations
        $connection->expects()
            ->getClient()
            ->never();

        // Actions
        $result = $repository->repositoryExists();

        // Assertions
        $this->assertTrue($result);
    }
}
