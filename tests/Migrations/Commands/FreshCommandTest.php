<?php

namespace MongolidLaravel\Migrations\Commands;

use Illuminate\Foundation\Application;
use Mockery as m;
use MongoDB\Client;
use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use MongolidLaravel\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class FreshCommandTest extends TestCase
{
    public function testShouldConfirmToRun()
    {
        // Set
        $pool = m::mock(Pool::class);
        $command = m::mock(FreshCommand::class.'[confirmToProceed]', [$pool]);
        $app = new Application();
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $command->expects()
            ->confirmToProceed()
            ->andReturn(false);

        $pool->expects()
            ->getConnection()
            ->never();

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }

    public function testShouldRunFreshCommand()
    {
        // Set
        $pool = m::mock(Pool::class);
        $command = m::mock(FreshCommand::class.'[call]', [$pool]);
        $app = m::mock(Application::class.'[environment]');
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);

        // Expectations
        $app->expects()
            ->environment()
            ->andReturn('development');

        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
            ->andReturn($client);

        $client->expects()
            ->dropDatabase('mongolid');

        $command->expects()
            ->call(
                'mongolid-migrate',
                [
                    '--database' => null,
                    '--path' => null,
                    '--realpath' => null,
                    '--force' => true,
                ]
            );

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }

    public function testShouldRunFreshCommandWithDbSeed()
    {
        // Set
        $pool = m::mock(Pool::class);
        $command = m::mock(FreshCommand::class.'[call]', [$pool]);
        $app = m::mock(Application::class.'[environment]');
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);

        // Expectations
        $app->expects()
            ->environment()
            ->andReturn('development');

        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
            ->andReturn($client);

        $client->expects()
            ->dropDatabase('mongolid');

        $command->expects()
            ->call(
                'mongolid-migrate',
                [
                    '--database' => null,
                    '--path' => null,
                    '--realpath' => null,
                    '--force' => true,
                ]
            );

        $command->expects()
            ->call(
                'db:seed',
                [
                    '--database' => null,
                    '--class' => 'DatabaseSeeder',
                    '--force' => false,
                ]
            );

        // Actions
        $command->run(new ArrayInput(['--seed' => true]), new NullOutput());
    }
}
