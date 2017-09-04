<?php

namespace MongolidLaravel;

use Mockery as m;
use MongoDB\BSON\ObjectID;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\DeleteResult;
use MongoDB\InsertOneResult;
use Mongolid\Connection\Pool;
use Mongolid\Connection\Connection;
use TestCase;
use stdClass;

class FailedJobsServiceTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAllShouldReturnWholeCollection()
    {
        // Set
        $connPool = m::mock(Pool::class);
        $rawCollection = m::mock(Collection::class);
        $this->mockRawCollection($connPool, $rawCollection);
        $cursor = m::mock(stdClass::class);

        $failedJobs = new FailedJobsService($connPool);

        // Expectations
        $rawCollection->shouldReceive('find')
            ->withNoArgs()
            ->once()
            ->andReturn($cursor);

        // Actions
        $result = $failedJobs->all();

        // Assertion
        $this->assertSame($cursor, $result);
    }

    public function testFindShouldReturnWholeCollection()
    {
        // Set
        $connPool = m::mock(Pool::class);
        $rawCollection = m::mock(Collection::class);
        $this->mockRawCollection($connPool, $rawCollection);
        $job = ['job' => 'attributes'];
        $id = '59a86805401fec4f572fdd21';

        $failedJobs = new FailedJobsService($connPool);

        // Expectations
        $rawCollection->shouldReceive('findOne')
            ->with(['_id' => new ObjectID($id)])
            ->once()
            ->andReturn($job);

        // Actions
        $result = $failedJobs->find($id);

        // Assertion
        $this->assertSame($job, $result);
    }

    public function testInsertShouldAddOneJob()
    {
        // Set
        $connPool = m::mock(Pool::class);
        $rawCollection = m::mock(Collection::class);
        $this->mockRawCollection($connPool, $rawCollection);
        $job = ['job' => 'attributes'];
        $resultInsert = m::mock(InsertOneResult::class);

        $failedJobs = new FailedJobsService($connPool);

        // Expectations
        $rawCollection->shouldReceive('insertOne')
            ->with($job)
            ->once()
            ->andReturn($resultInsert);

        // Actions
        $result = $failedJobs->insert($job);

        // Assertion
        $this->assertSame($resultInsert, $result);
    }

    public function testDeleteShouldRemoveOneJob()
    {
        // Set
        $connPool = m::mock(Pool::class);
        $rawCollection = m::mock(Collection::class);
        $this->mockRawCollection($connPool, $rawCollection);
        $id = '59a86805401fec4f572fdd21';
        $resultDelete = m::mock(DeleteResult::class);

        $failedJobs = new FailedJobsService($connPool);

        // Expectations
        $rawCollection->shouldReceive('deleteOne')
            ->with(['_id' => new ObjectID($id)])
            ->once()
            ->andReturn($resultDelete);

        // Actions
        $result = $failedJobs->delete($id);

        // Assertion
        $this->assertSame($resultDelete, $result);
    }

    public function testDropShouldCleanWholeCollection()
    {
        // Set
        $connPool = m::mock(Pool::class);
        $rawCollection = m::mock(Collection::class);
        $this->mockRawCollection($connPool, $rawCollection);

        $failedJobs = new FailedJobsService($connPool);

        // Expectations
        $rawCollection->shouldReceive('drop')
            ->withNoArgs()
            ->once();

        // Actions
        $failedJobs->drop();
    }

    private function mockRawCollection($connPool, $rawCollection, $collection = 'failed_jobs')
    {
        $connection = m::mock(Connection::class);
        $rawClient = m::mock(Client::class);

        $connection->defaultDatabase = 'database';
        $database = new stdClass();
        $database->{$collection} = $rawCollection;
        $rawClient->database = $database;

        $connPool->shouldReceive('getConnection')
            ->withNoArgs()
            ->once()
            ->andReturn($connection);

        $connection->shouldReceive('getRawConnection')
            ->withNoArgs()
            ->once()
            ->andReturn($rawClient);
    }
}
