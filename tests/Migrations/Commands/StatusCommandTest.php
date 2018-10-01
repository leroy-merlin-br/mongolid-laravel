<?php

namespace MongolidLaravel\Migrations\Commands;

use Illuminate\Foundation\Application;
use Mockery as m;
use MongolidLaravel\Migrations\MigrationRepositoryInterface;
use MongolidLaravel\Migrations\Migrator;
use MongolidLaravel\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class StatusCommandTest extends TestCase
{
    public function testShouldNotRunCommandWhenRepositoryDoesNotExist()
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = new StatusCommand($migrator);
        $app = new Application();
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $migrator->expects()
            ->setConnection(null);

        $migrator->expects()
            ->repositoryExists()
            ->andReturn(false);

        $migrator->expects()
            ->getRepository()
            ->never();

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }

    public function testShouldRunCommand()
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = new StatusCommand($migrator);
        $app = new Application();
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $repository = m::mock(MigrationRepositoryInterface::class);

        $ran = ['create_users_index', 'drop_admin'];
        $batches = ['create_users_index' => 1, 'drop_admin' => 2];
        $files = ['/database'];
        $migrationFiles = [
            'create_users_index' => 'database/migrations/create_users_index.php',
            'drop_admin' => 'database/migrations/drop_admin.php',
        ];

        // Expectations
        $migrator->expects()
            ->setConnection(null);

        $migrator->expects()
            ->repositoryExists()
            ->andReturn(true);

        $migrator->expects()
            ->getRepository()
            ->twice()
            ->andReturn($repository);

        $repository->expects()
            ->getRan()
            ->andReturn($ran);

        $repository->expects()
            ->getMigrationBatches()
            ->andReturn($batches);

        $migrator->expects()
            ->getMigrationFiles($files)
            ->andReturn($migrationFiles);

        $migrator->expects()
            ->getMigrationName('database/migrations/create_users_index.php')
            ->andReturn('create_users_index');

        $migrator->expects()
            ->getMigrationName('database/migrations/drop_admin.php')
            ->andReturn('drop_admin');

        // Actions
        $command->run(new ArrayInput(['--path' => 'database']), new NullOutput());
    }

    public function testShouldRunCommandWithNoMigrations()
    {
        // Set
        $migrator = m::mock(Migrator::class);
        $command = new StatusCommand($migrator);
        $app = new Application();
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $repository = m::mock(MigrationRepositoryInterface::class);

        $ran = [];
        $batches = [];
        $files = ['/database'];
        $migrationFiles = [];

        // Expectations
        $migrator->expects()
            ->setConnection(null);

        $migrator->expects()
            ->repositoryExists()
            ->andReturn(true);

        $migrator->expects()
            ->getRepository()
            ->twice()
            ->andReturn($repository);

        $repository->expects()
            ->getRan()
            ->andReturn($ran);

        $repository->expects()
            ->getMigrationBatches()
            ->andReturn($batches);

        $migrator->expects()
            ->getMigrationFiles($files)
            ->andReturn($migrationFiles);

        // Actions
        $command->run(new ArrayInput(['--path' => 'database']), new NullOutput());
    }
}
