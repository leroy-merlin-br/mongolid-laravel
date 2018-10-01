<?php
namespace MongolidLaravel\Migrations;

use MongoDB\Collection;
use Mongolid\Connection\Pool;

class MongolidMigrationRepository implements MigrationRepositoryInterface
{
    /**
     * The name of the migration collection.
     *
     * @var string
     */
    private $collection;

    /**
     * The name of the database to use.
     *
     * @var string|null
     */
    private $database;

    /**
     * @var Pool
     */
    private $pool;

    /**
     * @var Collection
     */
    private $cachedCollection;

    public function __construct(Pool $pool, string $collection)
    {
        $this->collection = $collection;
        $this->pool = $pool;
    }

    public function getRan()
    {
        $results = $this->collection()
            ->find(
                [],
                [
                    'sort' => ['batch' => 1, 'migration' => 1],
                    'projection' => ['_id' => 0, 'migration' => 1],
                ]
            );

        return collect($results)->pluck('migration')->all();
    }

    public function getMigrations($steps)
    {
        return $this->collection()
            ->find(
                ['batch' => ['$gte' => 1]],
                [
                    'sort' => ['batch' => -1, 'migration' => -1],
                    'limit' => $steps,
                ]
            )->toArray();
    }

    public function getLast()
    {
        return $this->collection()->find(
            ['batch' => $this->getLastBatchNumber()],
            ['sort' => ['migration' => -1]]
        )->toArray();
    }

    public function getMigrationBatches()
    {
        $results = $this->collection()
            ->find(
                [],
                [
                    'sort' => ['batch' => 1, 'migration' => 1],
                ]
            );

        return collect($results)->pluck('batch', 'migration')->all();
    }

    public function log($file, $batch)
    {
        $record = ['migration' => $file, 'batch' => $batch];

        $this->collection()->insertOne($record);
    }

    public function delete($migration)
    {
        $this->collection()->deleteOne(['migration' => $migration->migration]);
    }

    /**
     * Get the next migration batch number.
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    public function getLastBatchNumber()
    {
        $results = $this->collection()
            ->find(
                [],
                [
                    'projection' => ['_id' => 0, 'batch' => 1],
                    'sort' => ['batch' => -1],
                    'limit' => 1,
                ]
            )->toArray();

        return $results[0]->batch ?? 0;
    }

    public function createRepository()
    {
    }

    public function repositoryExists()
    {
        return true;
    }

    public function setSource($name)
    {
        $this->database = $name;
    }

    private function collection(): Collection
    {
        if (!$this->cachedCollection) {
            $connection = $this->pool->getConnection();
            $database = $this->database ?? $connection->defaultDatabase;

            $this->cachedCollection = $connection->getRawConnection()
                ->selectCollection($database, $this->collection);
        }

        return $this->cachedCollection;
    }
}
