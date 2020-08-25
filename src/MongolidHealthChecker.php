<?php
namespace MongolidLaravel;

use Exception;
use MongoDB\Driver\Command;
use Mongolid\Connection\Connection;

class MongolidHealthChecker
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function isHealthy(): bool
    {
        $command = new Command(['ping' => 1]);

        try {
            $this->connection->getRawManager()->executeCommand('admin', $command);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
