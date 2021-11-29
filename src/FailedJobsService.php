<?php
namespace Mongolid\Laravel;

use MongoDB\BSON\ObjectID;
use MongoDB\Collection;
use MongoDB\DeleteResult;
use MongoDB\Driver\Cursor;
use MongoDB\InsertOneResult;
use Mongolid\Connection\Connection;

/**
 * Persistence layer that is used to save failed queue jobs on MongoDB.
 */
class FailedJobsService
{
    /**
     * Collection name. Default 'failed_jobs'.
     *
     * @var string
     */
    protected $collection;

    /**
     * Connections that are going to be used to interact with database.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection Connections that are going to be used to interact with MongoDB
     * @param string     $collection Collection where jobs will be stored
     */
    public function __construct(Connection $connection, string $collection = 'failed_jobs')
    {
        $this->connection = $connection;
        $this->collection = $collection;
    }

    /**
     * Return a Cursor with all collection entries.
     *
     * @return Cursor
     */
    public function all()
    {
        return $this->rawCollection()->find();
    }

    /**
     * Retrieve a single job from collection.
     *
     * @return mixed
     */
    public function find(string $id)
    {
        return $this->rawCollection()->findOne(['_id' => new ObjectID($id)]);
    }

    /**
     * Insert a job on collection.
     *
     * @param array $attributes
     */
    public function insert(array $attributes): InsertOneResult
    {
        return $this->rawCollection()->insertOne($attributes);
    }

    /**
     * Remove a job from collection.
     */
    public function delete(string $id): DeleteResult
    {
        return $this->rawCollection()->deleteOne(['_id' => new ObjectID($id)]);
    }

    /**
     * Drops collection, removing all jobs.
     */
    public function drop()
    {
        $this->rawCollection()->drop();
    }

    /**
     * Get the actual MongoDB Collection object.
     */
    protected function rawCollection(): Collection
    {
        $database = $this->connection->defaultDatabase;

        return $this->connection->getRawConnection()->{$database}->{$this->collection};
    }
}
