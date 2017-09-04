<?php

namespace MongolidLaravel;

use ArrayObject;
use Exception;
use Mockery as m;
use MongoDB\DeleteResult;
use MongoDB\InsertOneResult;
use PHPUnit\Framework\TestCase;

class MongolidFailedJobProviderTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testLogShouldPersistFailedJob()
    {
        // Set
        $service = m::mock(FailedJobsService::class);
        $provider = new MongolidFailedJobProvider($service);

        $insertResult = m::mock(InsertOneResult::class);

        $connection = 'sqs';
        $queue = 'heavy';
        $payload = json_encode(['some' => 'payload']);
        $exception = new Exception();

        // Expectations
        $service->shouldReceive('insert')
            ->with(
                [
                    'connection' => $connection,
                    'queue' => $queue,
                    'payload' => $payload,
                    'exception' => (string) $exception,
                ]
            )
            ->once()
            ->andReturn($insertResult);

        $insertResult->shouldReceive('getInsertedCount')
            ->withNoArgs()
            ->once()
            ->andReturn(1);

        // Actions
        $result = $provider->log($connection, $queue, $payload, $exception);

        // Assertions
        $this->assertEquals(1, $result);
    }

    public function testAllShouldReturnAllJobs()
    {
        // Set
        $service = m::mock(FailedJobsService::class);
        $provider = new MongolidFailedJobProvider($service);

        $data = [
            [
                'id' => 'xpto1',
                'connection' => 'sqs',
                'queue' => 'heavy',
                'payload' => '{some:json}',
                'exception' => 'Exception: Xtpo',
            ],
            [
                'id' => 'xpto2',
                'connection' => 'sqs',
                'queue' => 'heavy',
                'payload' => '{some:json}',
                'exception' => 'Exception: Xtpo',
            ],
        ];
        $cursor = new ArrayObject($data);

        // Expectations
        $service->shouldReceive('all')
            ->withAnyArgs()
            ->once()
            ->andReturn($cursor);

        // Actions
        $result = $provider->all();

        // Assertions
        $this->assertEquals($data, $result);
    }

    public function testFindShouldReturnJob()
    {
        // Set
        $service = m::mock(FailedJobsService::class);
        $provider = new MongolidFailedJobProvider($service);

        $id = 'xpto1';
        $data = [
            'id' => 'xpto1',
            'connection' => 'sqs',
            'queue' => 'heavy',
            'payload' => '{some:json}',
            'exception' => 'Exception: Xtpo',
        ];

        // Expectations
        $service->shouldReceive('find')
            ->with($id)
            ->once()
            ->andReturn($data);

        // Actions
        $result = $provider->find($id);

        // Assertions
        $this->assertEquals($data, $result);
    }

    public function testForgetShouldDeleteJob()
    {
        // Set
        $service = m::mock(FailedJobsService::class);
        $provider = new MongolidFailedJobProvider($service);

        $id = 'xpto1';
        $deletedResult = m::mock(DeleteResult::class);

        // Expectations
        $service->shouldReceive('delete')
            ->with($id)
            ->once()
            ->andReturn($deletedResult);

        $deletedResult->shouldReceive('getDeletedCount')
            ->withNoArgs()
            ->once()
            ->andReturn(1);

        // Actions
        $result = $provider->forget($id);

        // Assertions
        $this->assertTrue($result);
    }

    public function testFlushShouldDropWholeCollection()
    {
        // Set
        $service = m::mock(FailedJobsService::class);
        $provider = new MongolidFailedJobProvider($service);

        // Expectations
        $service->shouldReceive('drop')
            ->withNoArgs()
            ->once();

        // Actions
        $provider->flush();
    }
}
