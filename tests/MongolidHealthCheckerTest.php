<?php
namespace MongolidLaravel;

use Mockery as m;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use MongoDB\Driver\Exception\ConnectionTimeoutException;
use MongoDB\Driver\Manager;
use Mongolid\Connection\Connection;
use PHPUnit\Framework\MockObject\MockObject;

class MongolidHealthCheckerTest extends TestCase
{
    /**
     * @var MockObject|Connection
     */
    private $connection;

    /**
     * @var LegacyMockInterface|MockInterface|Manager
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->manager = m::mock(new Manager('mongodb://foo'));
    }

    private function init(): MongolidHealthChecker
    {
        return new MongolidHealthChecker($this->connection);
    }

    public function testHealthOK(): void
    {
        // Set
        $checker = $this->init();

        // Expectations
        $this->connection->expects($this->once())
            ->method('getRawManager')
            ->willReturn($this->manager);

        $this->manager->shouldReceive('executeCommand')
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
        $checker = $this->init();

        // Expectations
        $this->connection->expects($this->once())
            ->method('getRawManager')
            ->willReturn($this->manager);

        $this->manager->shouldReceive('executeCommand')
            ->andThrow(new ConnectionTimeoutException(
                "No suitable servers found (`serverSelectionTryOnce` set): [Failed to resolve 'foo']"
            ));

        // Actions
        $healthStatus = $checker->isHealthy();

        // Assertions
        $this->assertFalse($healthStatus);
    }
}
