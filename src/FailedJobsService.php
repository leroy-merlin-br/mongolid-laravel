<?php

namespace MongolidLaravel;

use MongoDB\BSON\ObjectID;
use MongoDB\Collection;
use MongoDB\DeleteResult;
use MongoDB\Driver\Cursor;
use MongoDB\InsertOneResult;
use Mongolid\Connection\Pool;

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
     * @var Pool
     */
    protected $connPool;

    /**
     * @param Pool   $connPool   Connections that are going to be used to interact with MongoDB
     * @param string $collection Collection where jobs will be stored
     */
    public function __construct(Pool $connPool, string $collection = 'failed_jobs')
    {
        $this->connPool = $connPool;
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
     * @param string $id
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
     *
     * @return InsertOneResult
     */
    public function insert(array $attributes): InsertOneResult
    {
        return $this->rawCollection()->insertOne($attributes);
    }

    /**
     * Remove a job from collection.
     *
     * @param string $id
     *
     * @return DeleteResult
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
     *
     * @return Collection
     */
    protected function rawCollection(): Collection
    {
        $conn = $this->connPool->getConnection();
        $database = $conn->defaultDatabase;

        return $conn->getRawConnection()->$database->{$this->collection};
    }
}
