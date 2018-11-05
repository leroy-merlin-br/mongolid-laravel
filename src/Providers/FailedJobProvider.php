<?php
namespace Mongolid\Laravel\Providers;

use DateTime;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Laravel\FailedJobsService;
use Mongolid\Util\LocalDateTime;

/**
 * Mongolid implementation to use Laravel Failed Queue Services Provider.
 */
class FailedJobProvider implements FailedJobProviderInterface
{
    /**
     * @var FailedJobsService
     */
    private $failedJobs;

    public function __construct(FailedJobsService $failedJobs)
    {
        $this->failedJobs = $failedJobs;
    }

    /**
     * Log a failed job into storage.
     *
     * @param string     $connection
     * @param string     $queue
     * @param string     $payload
     * @param \Exception $exception
     *
     * @return int
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $result = $this->failedJobs->insert(
            [
                'connection' => $connection,
                'queue' => $queue,
                'payload' => $payload,
                'exception' => (string) $exception,
                'failed_at' => new UTCDateTime(),
            ]
        );

        return (string) $result->getInsertedId();
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all()
    {
        foreach ($this->failedJobs->all() as $job) {
            $jobs[] = $this->presentJob($job);
        }

        return $jobs ?? [];
    }

    /**
     * Get a single failed job.
     *
     * @param mixed $id
     *
     * @return object|null
     */
    public function find($id)
    {
        return $this->failedJobs->find($id);
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param mixed $id
     *
     * @return bool
     */
    public function forget($id)
    {
        $result = $this->failedJobs->delete($id);

        return (bool) $result->getDeletedCount();
    }

    /**
     * Flush all of the failed jobs from storage.
     */
    public function flush()
    {
        $this->failedJobs->drop();
    }

    /**
     * Prepare job to be consumed by Laravel Commands.
     *
     * @param object $job
     *
     * @return object
     */
    private function presentJob($job)
    {
        $job = (array) $job;

        return (object) [
            'id' => (string) $job['_id'],
            'connection' => $job['connection'],
            'queue' => $job['queue'],
            'payload' => $job['payload'],
            'exception' => $job['exception'],
            'failed_at' => LocalDateTime::format($job['failed_at'], DateTime::ATOM),
        ];
    }
}
