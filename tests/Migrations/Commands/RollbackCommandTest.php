<?php

namespace MongolidLaravel\Migrations\Commands;

use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Application;
use Mockery as m;
use MongolidLaravel\Migrations\Migrator;
use MongolidLaravel\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class RollbackCommandTest extends TestCase
{
    public function testRollbackCommandCallsMigratorWithProperArguments()
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

    public function testRollbackCommandCallsMigratorWithStepOption()
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
}
