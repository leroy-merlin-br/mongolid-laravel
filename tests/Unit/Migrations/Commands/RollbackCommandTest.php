<?php
namespace Mongolid\Laravel\Migrations\Commands;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Application;
use Mockery as m;
use Mongolid\Laravel\Migrations\Migrator;
use Mongolid\Laravel\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class RollbackCommandTest extends TestCase
{
    public function testRollbackCommandCallsMigratorWithProperArguments(): void
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = new RollbackCommand($migrator);
        $app = m::mock(Application::class.'[environment]');
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $app->expects()
            ->environment()
            ->andReturn('development');

        $migrator->expects()
            ->paths()
            ->andReturn([]);

        $migrator->expects()
            ->setConnection(null);

        $migrator->expects()
            ->setOutput(m::type(OutputStyle::class))
            ->andReturn($migrator);

        $migrator->expects()
            ->rollback([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['step' => 0]);

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }

    public function testRollbackCommandCallsMigratorWithStepOption(): void
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = new RollbackCommand($migrator);
        $app = m::mock(Application::class.'[environment]');
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $app->expects()
            ->environment()
            ->andReturn('development');

        $migrator->expects()
            ->paths()
            ->andReturn([]);

        $migrator->expects()
            ->setConnection(null);

        $migrator->expects()
            ->setOutput(m::type(OutputStyle::class))
            ->andReturn($migrator);

        $migrator->expects()
            ->rollback([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['step' => 2]);

        // Actions
        $command->run(new ArrayInput(['--step' => 2]), new NullOutput());
    }

    public function testShouldConfirmToRun(): void
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = m::mock(RollbackCommand::class.'[confirmToProceed]', [$migrator]);
        $app = new Application();
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $command->expects()
            ->confirmToProceed()
            ->andReturn(false);

        $migrator->expects()
            ->paths()
            ->never();

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }
}
