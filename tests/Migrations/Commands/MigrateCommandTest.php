<?php

namespace MongolidLaravel\Migrations\Commands;

use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Application;
use Mockery as m;
use MongolidLaravel\Migrations\Migrator;
use MongolidLaravel\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class MigrateCommandTest extends TestCase
{
    public function testBasicMigrationsCallMigratorWithProperArguments()
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = new MigrateCommand($migrator);
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
            ->run([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['step' => false]);

        $migrator->expects()
            ->repositoryExists()
            ->andReturn(true);

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }

    public function testMigrationRepositoryCreatedWhenNecessary()
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = m::mock(MigrateCommand::class.'[call]', [$migrator]);
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
            ->run([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['step' => false]);

        $migrator->expects()
            ->repositoryExists()
            ->andReturn(false);

        $command->expects()
            ->call(
                'mongolid-migrate:install',
                ['--database' => null]
            );

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }

    public function testTheDatabaseMayBeSet()
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = new MigrateCommand($migrator);
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
            ->setConnection('foo');

        $migrator->expects()
            ->setOutput(m::type(OutputStyle::class))
            ->andReturn($migrator);

        $migrator->expects()
            ->run([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['step' => false]);

        $migrator->expects()
            ->repositoryExists()
            ->andReturn(true);

        // Actions
        $command->run(new ArrayInput(['--database' => 'foo']), new NullOutput());
    }

    public function testStepMayBeSet()
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = new MigrateCommand($migrator);
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
            ->run([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['step' => true]);

        $migrator->expects()
            ->repositoryExists()
            ->andReturn(true);

        // Actions
        $command->run(new ArrayInput(['--step' => true]), new NullOutput());
    }
}
