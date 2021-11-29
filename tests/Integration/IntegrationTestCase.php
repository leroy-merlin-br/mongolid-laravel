<?php
namespace Mongolid\Laravel\Tests\Integration;

use Mongolid\Laravel\TestCase;
use Mongolid\Laravel\Tests\Util\DropDatabaseTrait;
use Mongolid\Laravel\Tests\Util\SetupConnectionTrait;

class IntegrationTestCase extends TestCase
{
    use DropDatabaseTrait;
    use SetupConnectionTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $host = getenv('DB_HOST') ?: 'localhost';
        $database = getenv('DB_DATABASE') ?: 'testing';

        $this->setupConnection($host, $database);
        $this->dropDatabase();
    }

    protected function tearDown(): void
    {
        $this->dropDatabase();
        parent::tearDown();
    }
}
