<?php
namespace Mongolid\Laravel\Migrations;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Illuminate\Console\OutputStyle;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Migrator
{
    /**
     * The migration repository implementation.
     *
     * @var \Mongolid\Laravel\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The name of the default connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * The paths to all of the migration files.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * The output interface implementation.
     *
     * @var \Illuminate\Console\OutputStyle
     */
    protected $output;

    /**
     * Create a new migrator instance.
     */
    public function __construct(MigrationRepositoryInterface $repository, Filesystem $files)
    {
        $this->files = $files;
        $this->repository = $repository;
    }

    /**
     * Run the pending migrations at a given path.
     *
     * @param array|string $paths
     * @param array        $options
     *
     * @return array
     */
    public function run($paths = [], array $options = [])
    {
        $this->notes = [];

        // Once we grab all of the migration files for the path, we will compare them
        // against the migrations that have already been run for this package then
        // run each of the outstanding migrations against a database connection.
        $files = $this->getMigrationFiles($paths);

        $this->requireFiles(
            $migrations = $this->pendingMigrations(
                $files,
                $this->repository->getRan()
            )
        );

        // Once we have all these migrations that are outstanding we are ready to run
        // we will go ahead and run them "up". This will execute each migration as
        // an operation against a database. Then we'll return this list of them.
        $this->runPending($migrations, $options);

        return $migrations;
    }

    /**
     * Get the migration files that have not yet run.
     *
     * @param array $files
     * @param array $ran
     *
     * @return array
     */
    protected function pendingMigrations($files, $ran)
    {
        return Collection::make($files)
            ->reject(
                function ($file) use ($ran) {
                    return in_array($this->getMigrationName($file), $ran);
                }
            )->values()->all();
    }

    /**
     * Run an array of migrations.
     *
     * @param array $migrations
     * @param array $options
     */
    public function runPending(array $migrations, array $options = [])
    {
        // First we will just make sure that there are any migrations to run. If there
        // aren't, we will just make a note of it to the developer so they're aware
        // that all of the migrations have been run against this database system.
        if (0 === count($migrations)) {
            $this->note('<info>Nothing to migrate.</info>');

            return;
        }

        // Next, we will get the next batch number for the migrations so we can insert
        // correct batch number in the database migrations repository when we store
        // each migration's execution. We will also extract a few of the options.
        $batch = $this->repository->getNextBatchNumber();

        $step = $options['step'] ?? false;

        // Once we have the array of migrations, we will spin through them and run the
        // migrations "up" so the changes are made to the databases. We'll then log
        // that the migration was run so we don't repeat it next time we execute.
        foreach ($migrations as $file) {
            $this->runUp($file, $batch);

            if ($step) {
                ++$batch;
            }
        }
    }

    /**
     * Run "up" a migration instance.
     *
     * @param string $file
     * @param int    $batch
     */
    protected function runUp($file, $batch)
    {
        // First we will resolve a "real" instance of the migration class from this
        // migration file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        $migration = $this->resolve(
            $name = $this->getMigrationName($file)
        );

        $this->note("<comment>Migrating:</comment> {$name}");

        $this->runMigration($migration, 'up');

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $this->repository->log($name, $batch);

        $this->note("<info>Migrated:</info>  {$name}");
    }

    /**
     * Rollback the last migration operation.
     *
     * @param array|string $paths
     * @param array        $options
     *
     * @return array
     */
    public function rollback($paths = [], array $options = [])
    {
        $this->notes = [];

        // We want to pull in the last batch of migrations that ran on the previous
        // migration operation. We'll then reverse those migrations and run each
        // of them "down" to reverse the last migration "operation" which ran.
        $migrations = $this->getMigrationsForRollback($options);

        if (0 === count($migrations)) {
            $this->note('<info>Nothing to rollback.</info>');

            return [];
        }

        return $this->rollbackMigrations($migrations, $paths);
    }

    /**
     * Get the migrations for a rollback operation.
     *
     * @param array $options
     *
     * @return array
     */
    protected function getMigrationsForRollback(array $options)
    {
        if (($steps = $options['step'] ?? 0) > 0) {
            return $this->repository->getMigrations($steps);
        }

        return $this->repository->getLast();
    }

    /**
     * Rollback the given migrations.
     *
     * @param array        $migrations
     * @param array|string $paths
     *
     * @return array
     */
    protected function rollbackMigrations(array $migrations, $paths)
    {
        $rolledBack = [];

        $this->requireFiles($files = $this->getMigrationFiles($paths));

        // Next we will run through all of the migrations and call the "down" method
        // which will reverse each migration in order. This getLast method on the
        // repository already returns these migration's names in reverse order.
        foreach ($migrations as $migration) {
            $migration = (object) $migration;

            if (!$file = Arr::get($files, $migration->migration)) {
                $this->note("<fg=red>Migration not found:</> {$migration->migration}");

                continue;
            }

            $rolledBack[] = $file;

            $this->runDown($file, $migration);
        }

        return $rolledBack;
    }

    /**
     * Rolls all of the currently applied migrations back.
     *
     * @param array|string $paths
     *
     * @return array
     */
    public function reset($paths = [])
    {
        $this->notes = [];

        // Next, we will reverse the migration list so we can run them back in the
        // correct order for resetting this database. This will allow us to get
        // the database back into its "empty" state ready for the migrations.
        $migrations = array_reverse($this->repository->getRan());

        if (0 === count($migrations)) {
            $this->note('<info>Nothing to rollback.</info>');

            return [];
        }

        return $this->resetMigrations($migrations, $paths);
    }

    /**
     * Reset the given migrations.
     *
     * @param array $migrations
     * @param array $paths
     *
     * @return array
     */
    protected function resetMigrations(array $migrations, array $paths)
    {
        // Since the getRan method that retrieves the migration name just gives us the
        // migration name, we will format the names into objects with the name as a
        // property on the objects so that we can pass it to the rollback method.
        $migrations = collect($migrations)->map(
            function ($m) {
                return (object) ['migration' => $m];
            }
        )->all();

        return $this->rollbackMigrations($migrations, $paths);
    }

    /**
     * Run "down" a migration instance.
     *
     * @param string $file
     * @param object $migration
     */
    protected function runDown($file, $migration)
    {
        // First we will get the file name of the migration so we can resolve out an
        // instance of the migration. Once we get an instance we can run the migration.
        $instance = $this->resolve(
            $name = $this->getMigrationName($file)
        );

        $this->note("<comment>Rolling back:</comment> {$name}");

        $this->runMigration($instance, 'down');

        // Once we have successfully run the migration "down" we will remove it from
        // the migration repository so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        $this->repository->delete($migration);

        $this->note("<info>Rolled back:</info>  {$name}");
    }

    /**
     * Run a migration inside a transaction if the database supports it.
     *
     * @param object $migration
     * @param string $method
     */
    protected function runMigration($migration, $method)
    {
        $callback = function () use ($migration, $method) {
            if (method_exists($migration, $method)) {
                $migration->{$method}();
            }
        };

        $callback();
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param string $file
     *
     * @return object
     */
    public function resolve($file)
    {
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));

        return new $class($this->output);
    }

    /**
     * Get all of the migration files in a given path.
     *
     * @param string|array $paths
     *
     * @return array
     */
    public function getMigrationFiles($paths)
    {
        return Collection::make($paths)->flatMap(
            function ($path) {
                return $this->files->glob($path.'/*_*.php');
            }
        )->filter()->sortBy(
            function ($file) {
                return $this->getMigrationName($file);
            }
        )->values()->keyBy(
            function ($file) {
                return $this->getMigrationName($file);
            }
        )->all();
    }

    /**
     * Require in all the migration files in a given path.
     *
     * @param array $files
     */
    public function requireFiles(array $files)
    {
        foreach ($files as $file) {
            $this->files->requireOnce($file);
        }
    }

    /**
     * Get the name of the migration.
     *
     * @param string $path
     *
     * @return string
     */
    public function getMigrationName($path)
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Register a custom migration path.
     *
     * @param string $path
     */
    public function path($path)
    {
        $this->paths = array_unique(array_merge($this->paths, [$path]));
    }

    /**
     * Get all of the custom migration paths.
     *
     * @return array
     */
    public function paths()
    {
        return $this->paths;
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     */
    public function setConnection($name)
    {
        $this->repository->setSource($name);

        $this->connection = $name;
    }

    /**
     * Get the migration repository instance.
     *
     * @return \Mongolid\Laravel\Migrations\MigrationRepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }

    /**
     * Get the file system instance.
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

    /**
     * Set the output implementation that should be used by the console.
     *
     * @return $this
     */
    public function setOutput(OutputStyle $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Write a note to the console's output.
     *
     * @param string $message
     */
    protected function note($message)
    {
        if ($this->output) {
            $this->output->writeln($message);
        }
    }
}
