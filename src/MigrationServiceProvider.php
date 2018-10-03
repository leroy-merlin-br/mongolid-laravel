<?php
namespace MongolidLaravel;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Illuminate\Support\ServiceProvider;
use Mongolid\Connection\Pool;
use MongolidLaravel\Migrations\Commands\FreshCommand;
use MongolidLaravel\Migrations\Commands\InstallCommand;
use MongolidLaravel\Migrations\Commands\MigrateCommand;
use MongolidLaravel\Migrations\Commands\MigrateMakeCommand;
use MongolidLaravel\Migrations\Commands\RefreshCommand;
use MongolidLaravel\Migrations\Commands\ResetCommand;
use MongolidLaravel\Migrations\Commands\RollbackCommand;
use MongolidLaravel\Migrations\Commands\StatusCommand;
use MongolidLaravel\Migrations\MigrationCreator;
use MongolidLaravel\Migrations\Migrator;
use MongolidLaravel\Migrations\MongolidMigrationRepository;

class MigrationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    'command.mongolid-migrate.fresh',
                    'command.mongolid-migrate.install',
                    'command.mongolid-migrate',
                    'command.mongolid-migrate.make',
                    'command.mongolid-migrate.refresh',
                    'command.mongolid-migrate.reset',
                    'command.mongolid-migrate.rollback',
                    'command.mongolid-migrate.status',
                ]
            );
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerRepository();
        $this->registerMigrator();
        $this->registerCreator();

        $this->registerMigrateFreshCommand();
        $this->registerMigrateInstallCommand();
        $this->registerMigrateCommand();
        $this->registerMigrateMakeCommand();
        $this->registerMigrateRefreshCommand();
        $this->registerMigrateResetCommand();
        $this->registerMigrateRollbackCommand();
        $this->registerMigrateStatusCommand();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'mongolid.migrator',
            'mongolid.migration.repository',
            'mongolid.migration.creator',
            'command.mongolid-migrate',
            'command.mongolid-migrate.fresh',
            'command.mongolid-migrate.install',
            'command.mongolid-migrate.make',
            'command.mongolid-migrate.refresh',
            'command.mongolid-migrate.reset',
            'command.mongolid-migrate.rollback',
            'command.mongolid-migrate.status',
        ];
    }

    /**
     * Register the migration repository service.
     */
    protected function registerRepository()
    {
        $this->app->singleton(
            'mongolid.migration.repository',
            function ($app) {
                $collection = $app['config']['database.mongodb.default.migrations'] ?? 'migrations';

                return new MongolidMigrationRepository($app[Pool::class], $collection);
            }
        );
    }

    /**
     * Register the migrator service.
     */
    protected function registerMigrator()
    {
        // The migrator is responsible for actually running and rollback the migration
        // files in the application. We'll pass in our database connection resolver
        // so the migrator can resolve any of these connections when it needs to.
        $this->app->singleton(
            'mongolid.migrator',
            function ($app) {
                $repository = $app['mongolid.migration.repository'];

                return new Migrator($repository, $app['files']);
            }
        );
    }

    /**
     * Register the migration creator.
     */
    protected function registerCreator()
    {
        $this->app->singleton(
            'mongolid.migration.creator',
            function ($app) {
                return new MigrationCreator($app['files']);
            }
        );
    }

    /**
     * Register the command.
     */
    protected function registerMigrateCommand()
    {
        $this->app->singleton(
            'command.mongolid-migrate',
            function ($app) {
                return new MigrateCommand($app['mongolid.migrator']);
            }
        );
    }

    /**
     * Register the command.
     */
    protected function registerMigrateFreshCommand()
    {
        $this->app->singleton(
            'command.mongolid-migrate.fresh',
            function ($app) {
                return new FreshCommand($app[Pool::class]);
            }
        );
    }

    /**
     * Register the command.
     */
    protected function registerMigrateInstallCommand()
    {
        $this->app->singleton(
            'command.mongolid-migrate.install',
            function ($app) {
                return new InstallCommand($app['mongolid.migration.repository']);
            }
        );
    }

    /**
     * Register the command.
     */
    protected function registerMigrateMakeCommand()
    {
        $this->app->singleton(
            'command.mongolid-migrate.make',
            function ($app) {
                // Once we have the migration creator registered, we will create the command
                // and inject the creator. The creator is responsible for the actual file
                // creation of the migrations, and may be extended by these developers.
                $creator = $app['mongolid.migration.creator'];

                $composer = $app['composer'];

                return new MigrateMakeCommand($creator, $composer);
            }
        );
    }

    /**
     * Register the command.
     */
    protected function registerMigrateRefreshCommand()
    {
        $this->app->singleton(
            'command.mongolid-migrate.refresh',
            function () {
                return new RefreshCommand();
            }
        );
    }

    /**
     * Register the command.
     */
    protected function registerMigrateResetCommand()
    {
        $this->app->singleton(
            'command.mongolid-migrate.reset',
            function ($app) {
                return new ResetCommand($app['mongolid.migrator']);
            }
        );
    }

    /**
     * Register the command.
     */
    protected function registerMigrateRollbackCommand()
    {
        $this->app->singleton(
            'command.mongolid-migrate.rollback',
            function ($app) {
                return new RollbackCommand($app['mongolid.migrator']);
            }
        );
    }

    /**
     * Register the command.
     */
    protected function registerMigrateStatusCommand()
    {
        $this->app->singleton(
            'command.mongolid-migrate.status',
            function ($app) {
                return new StatusCommand($app['mongolid.migrator']);
            }
        );
    }
}
