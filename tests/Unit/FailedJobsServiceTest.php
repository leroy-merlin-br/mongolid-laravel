<?php
namespace Mongolid\Laravel;

use Mockery as m;
use MongoDB\BSON\ObjectID;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\DeleteResult;
use MongoDB\InsertOneResult;
use Mongolid\Connection\Connection;
use stdClass;

class FailedJobsServiceTest extends TestCase
{
    public function testAllShouldReturnWholeCollection(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $rawCollection = m::mock(Collection::class);
        $this->mockRawCollection($connection, $rawCollection);
        $cursor = m::mock(stdClass::class);

        $failedJobs = new FailedJobsService($connection);

        // Expectations
        $rawCollection->expects()
            ->find()
            ->andReturn($cursor);

        // Actions
        $result = $failedJobs->all();

        // Assertion
        $this->assertSame($cursor, $result);
    }

    public function testFindShouldReturnWholeCollection(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $rawCollection = m::mock(Collection::class);
        $this->mockRawCollection($connection, $rawCollection);
        $job = ['job' => 'attributes'];
        $id = '59a86805401fec4f572fdd21';

        $failedJobs = new FailedJobsService($connection);

        // Expectations
        $rawCollection->expects()
            ->findOne(['_id' => new ObjectID($id)])
            ->andReturn($job);

        // Actions
        $result = $failedJobs->find($id);

        // Assertion
        $this->assertSame($job, $result);
    }

    public function testInsertShouldAddOneJob(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $rawCollection = m::mock(Collection::class);
        $this->mockRawCollection($connection, $rawCollection);
        $job = ['job' => 'attributes'];
        $resultInsert = m::mock(InsertOneResult::class);

        $failedJobs = new FailedJobsService($connection);

        // Expectations
        $rawCollection->expects()
            ->insertOne($job)
            ->andReturn($resultInsert);

        // Actions
        $result = $failedJobs->insert($job);

        // Assertion
        $this->assertSame($resultInsert, $result);
    }

    public function testDeleteShouldRemoveOneJob(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $rawCollection = m::mock(Collection::class);
        $this->mockRawCollection($connection, $rawCollection);
        $id = '59a86805401fec4f572fdd21';
        $resultDelete = m::mock(DeleteResult::class);

        $failedJobs = new FailedJobsService($connection);

        // Expectations
        $rawCollection->expects()
            ->deleteOne()
            ->with(['_id' => new ObjectID($id)])
            ->andReturn($resultDelete);

        // Actions
        $result = $failedJobs->delete($id);

        // Assertion
        $this->assertSame($resultDelete, $result);
    }

    public function testDropShouldCleanWholeCollection(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $rawCollection = m::mock(Collection::class);
        $this->mockRawCollection($connection, $rawCollection);

        $failedJobs = new FailedJobsService($connection);

        // Expectations
        $rawCollection->expects()
            ->drop();

        // Actions
        $failedJobs->drop();
    }

    private function mockRawCollection(
        Connection $connection,
        Collection $rawCollection,
        string $collection = 'failed_jobs'
    ): void {
        $rawClient = m::mock(Client::class);

        $connection->defaultDatabase = 'database';
        $database = new stdClass();
        $database->{$collection} = $rawCollection;
        $rawClient->database = $database;

        $connection->expects()
            ->getClient()
            ->andReturn($rawClient);
    }
}
