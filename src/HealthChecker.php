<?php
namespace Mongolid\Laravel;

use Exception;
use MongoDB\Driver\Command;
use Mongolid\Connection\Connection;

class HealthChecker
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
            $this->connection->getClient()
                ->getManager()
                ->executeCommand($this->connection->defaultDatabase, $command);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
