<?php

namespace MongolidLaravel;

use ArrayObject;
use DateTimeInterface;
use Exception;
use Mockery as m;
use MongoDB\BSON\UTCDateTime;
use MongoDB\DeleteResult;
use MongoDB\InsertOneResult;
use Mongolid\Util\LocalDateTime;

class MongolidFailedJobProviderTest extends TestCase
{
    public function testLogShouldPersistFailedJob(): void
    {
        // Set
        $service = m::mock(FailedJobsService::class);
        $provider = new MongolidFailedJobProvider($service);

        $insertResult = m::mock(InsertOneResult::class);

        $connection = 'sqs';
        $queue = 'heavy';
        $payload = json_encode(['some' => 'payload']);
        $exception = new Exception();

        $insertData = [
            'connection' => $connection,
            'queue' => $queue,
            'payload' => $payload,
            'exception' => (string) $exception,
            'failed_at' => new UTCDateTime(),
        ];

        // Expectations
        $service->shouldReceive('insert')
            ->with($this->expectEquals($insertData))
            ->once()
            ->andReturn($insertResult);

        $insertResult->shouldReceive('getInsertedId')
            ->withNoArgs()
            ->once()
            ->andReturn('xpto1');

        // Actions
        $result = $provider->log($connection, $queue, $payload, $exception);

        // Assertions
        $this->assertEquals('xpto1', $result);
    }

    public function testAllShouldReturnAllJobs(): void
    {
        // Set
        $service = m::mock(FailedJobsService::class);
        $provider = new MongolidFailedJobProvider($service);

        $data = [
            [
                '_id' => 'xpto1',
                'connection' => 'sqs',
                'queue' => 'heavy',
                'payload' => '{some:json}',
                'exception' => 'Exception: Xtpo',
                'failed_at' => new UTCDateTime(),
            ],
            [
                '_id' => 'xpto2',
                'connection' => 'sqs',
                'queue' => 'heavy',
                'payload' => '{some:json}',
                'exception' => 'Exception: Xtpo',
                'failed_at' => new UTCDateTime(),
            ],
        ];

        foreach ($data as $job) {
            $job['id'] = $job['_id'];
            $job['failed_at'] = LocalDateTime::format(
                $job['failed_at'],
                DateTimeInterface::ATOM
            );
            unset($job['_id']);

            $expected[] = (object) $job;
        }

        $cursor = new ArrayObject($data);

        // Expectations
        $service->shouldReceive('all')
            ->withAnyArgs()
            ->once()
            ->andReturn($cursor);

        // Actions
        $result = $provider->all();

        // Assertions
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getIdsScenarios
     */
    public function testShouldReturnIds(array $expected, ?string $queue): void
    {
        // Set
        $service = m::mock(FailedJobsService::class);
        $provider = new MongolidFailedJobProvider($service);

        $data = [
            [
                '_id' => '12345',
                'connection' => 'sqs',
                'queue' => 'queue1',
                'payload' => '{some:json}',
                'exception' => 'Exception: Xtpo',
                'failed_at' => LocalDateTime::format(
                    new UTCDateTime(),
                    DateTimeInterface::ATOM
                ),
            ],
            [
                '_id' => '67890',
                'connection' => 'sqs',
                'queue' => 'queue2',
                'payload' => '{some:json}',
                'exception' => 'Exception: Xtpo',
                'failed_at' => LocalDateTime::format(
                    new UTCDateTime(),
                    DateTimeInterface::ATOM
                ),
            ],
        ];

        // Expectations
        $service->shouldReceive()
            ->all()
            ->andReturn($data);

        // Actions
        $result = $provider->ids($queue);

        // Assertions
        $this->assertEquals($expected, $result);
    }

    public function testFindShouldReturnJob(): void
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

    public function testForgetShouldDeleteJob(): void
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

    public function testFlushShouldDropWholeCollection(): void
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

    public static function getIdsScenarios(): array
    {
        return [
            'no queue' => [
                'expected' => ['12345', '67890'],
                'queue' => null,
            ],
            'with queue' => [
                'expected' => ['67890'],
                'queue' => 'queue2',
            ],
        ];
    }
}
