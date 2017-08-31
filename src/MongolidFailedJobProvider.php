<?php

namespace MongolidLaravel;

use Illuminate\Queue\Failed\FailedJobProviderInterface;

/**
 * Mongolid implementation to use Laravel Failed Queue Services Provider.
 */
class MongolidFailedJobProvider implements FailedJobProviderInterface
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
     * @param  string     $connection
     * @param  string     $queue
     * @param  string     $payload
     * @param  \Exception $exception
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
            ]
        );

        return $result->getInsertedCount();
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all()
    {
        foreach ($this->failedJobs->all() as $job) {
            $jobs[] = (array) $job;
        }

        return $jobs ?? [];
    }

    /**
     * Get a single failed job.
     *
     * @param  mixed $id
     *
     * @return array
     */
    public function find($id)
    {
        return (array) $this->failedJobs->find($id);
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed $id
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
}
