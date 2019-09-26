<?php
namespace Mongolid\Laravel\Migrations\Commands;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Illuminate\Foundation\Application;
use Mockery as m;
use MongoDB\Client;
use Mongolid\Connection\Connection;
use Mongolid\Laravel\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class FreshCommandTest extends TestCase
{
    public function testShouldConfirmToRun(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $command = m::mock(FreshCommand::class.'[confirmToProceed]', [$connection]);
        $app = new Application();
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $command->expects()
            ->confirmToProceed()
            ->andReturn(false);

        $connection->expects()
            ->getClient()
            ->never();

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }

    public function testShouldRunFreshCommand(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $command = m::mock(FreshCommand::class.'[call]', [$connection]);
        $app = m::mock(Application::class.'[environment]');
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        $client = m::mock(Client::class);

        // Expectations
        $app->expects()
            ->environment()
            ->andReturn('development');

        $connection->expects()
            ->getClient()
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

    public function testShouldRunFreshCommandWithDbSeed(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $command = m::mock(FreshCommand::class.'[call]', [$connection]);
        $app = m::mock(Application::class.'[environment]');
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        $client = m::mock(Client::class);

        // Expectations
        $app->expects()
            ->environment()
            ->andReturn('development');

        $connection->expects()
            ->getClient()
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
