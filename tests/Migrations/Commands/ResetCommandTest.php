<?php

namespace MongolidLaravel\Migrations\Commands;

use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Application;
use Mockery as m;
use MongolidLaravel\Migrations\Migrator;
use MongolidLaravel\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ResetCommandTest extends TestCase
{
    public function testResetCommandCallsMigratorWithProperArguments()
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = new ResetCommand($migrator);
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
            ->repositoryExists()
            ->andReturn(true);

        $migrator->expects()
            ->setOutput(m::type(OutputStyle::class))
            ->andReturn($migrator);

        $migrator->expects()
            ->reset([__DIR__.DIRECTORY_SEPARATOR.'migrations']);

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }

    public function testResetCommandShouldNotRunWhenRepositoryDoesNotExist()
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = new ResetCommand($migrator);
        $app = m::mock(Application::class.'[environment]');
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $app->expects()
            ->environment()
            ->andReturn('development');

        $migrator->expects()
            ->paths()
            ->never();

        $migrator->expects()
            ->setConnection(null);

        $migrator->expects()
            ->repositoryExists()
            ->andReturn(false);

        $migrator->expects()
            ->setOutput(m::type(OutputStyle::class))
            ->never();

        $migrator->expects()
            ->reset([__DIR__.DIRECTORY_SEPARATOR.'migrations'])
            ->never();

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }

    public function testShouldConfirmToRun()
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = m::mock(ResetCommand::class.'[confirmToProceed]', [$migrator]);
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
