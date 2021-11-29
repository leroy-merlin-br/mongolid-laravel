<?php
namespace Mongolid\Laravel;

use Mockery as m;
use MongoDB\Client;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use Mongolid\Connection\Connection;

class HealthCheckerTest extends TestCase
{
    public function testHealthOK(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'defaultTestDatabase';
        $client = m::mock(Client::class);
        $manager = m::mock(new Manager('mongodb://foo'));
        $checker = new HealthChecker($connection);

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->getManager()
            ->andReturn($manager);

        $manager->expects()
            ->executeCommand('defaultTestDatabase', m::type(Command::class))
            ->andReturn([
                'ok' => 1,
            ]);

        // Actions
        $healthStatus = $checker->isHealthy();

        // Assertions
        $this->assertTrue($healthStatus);
    }

    public function testHealthKO(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $client = m::mock(Client::class);
        $manager = m::mock(new Manager('mongodb://foo'));
        $checker = new HealthChecker($connection);

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->getManager()
            ->andReturn($manager);

        // Actions
        $healthStatus = $checker->isHealthy();

        // Assertions
        $this->assertFalse($healthStatus);
    }
}
