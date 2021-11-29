<?php
namespace Mongolid\Laravel\Providers;

use ArrayObject;
use DateTime;
use Exception;
use Mockery as m;
use MongoDB\BSON\UTCDateTime;
use MongoDB\DeleteResult;
use MongoDB\InsertOneResult;
use Mongolid\Laravel\FailedJobsService;
use Mongolid\Laravel\TestCase;
use Mongolid\Util\LocalDateTime;

class FailedJobProviderTest extends TestCase
{
    public function testLogShouldPersistFailedJob(): void
    {
        // Set
        $service = m::mock(FailedJobsService::class);
        $provider = new FailedJobProvider($service);

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
        $service->expects()
            ->insert($this->expectEquals($insertData))
            ->andReturn($insertResult);

        $insertResult->expects()
            ->getInsertedId()
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
        $provider = new FailedJobProvider($service);

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
                DateTime::ATOM
            );
            unset($job['_id']);

            $expected[] = (object) $job;
        }

        $cursor = new ArrayObject($data);

        // Expectations
        $service->expects()
            ->all()
            ->withAnyArgs()
            ->andReturn($cursor);

        // Actions
        $result = $provider->all();

        // Assertions
        $this->assertEquals($expected, $result);
    }

    public function testFindShouldReturnJob(): void
    {
        // Set
        $service = m::mock(FailedJobsService::class);
        $provider = new FailedJobProvider($service);

        $id = 'xpto1';
        $data = [
            'id' => 'xpto1',
            'connection' => 'sqs',
            'queue' => 'heavy',
            'payload' => '{some:json}',
            'exception' => 'Exception: Xtpo',
        ];

        // Expectations
        $service->expects()
            ->find($id)
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
        $provider = new FailedJobProvider($service);

        $id = 'xpto1';
        $deletedResult = m::mock(DeleteResult::class);

        // Expectations
        $service->expects()
            ->delete($id)
            ->andReturn($deletedResult);

        $deletedResult->expects()
            ->getDeletedCount()
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
        $provider = new FailedJobProvider($service);

        // Expectations
        $service->expects()
            ->drop();

        // Actions
        $provider->flush();
    }
}
