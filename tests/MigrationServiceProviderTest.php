<?php

namespace MongolidLaravel;

use Mockery as m;
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

class MigrationServiceProviderTest extends TestCase
{
    public function testShouldBoot()
    {
        // Set
        $provider = m::mock(MigrationServiceProvider::class.'[commands]', [$this->app]);

        // Expectations
        $provider->expects()
            ->commands(
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

        // Actions
        $provider->boot();
    }

    public function testShouldRegister()
    {
        // Set
        $provider = new MigrationServiceProvider($this->app);

        // Actions
        $provider->register();

        // Assertions
        $this->assertInstanceOf(MongolidMigrationRepository::class, $this->app['mongolid.migration.repository']);
        $this->assertInstanceOf(Migrator::class, $this->app['mongolid.migrator']);
        $this->assertInstanceOf(MigrationCreator::class, $this->app['mongolid.migration.creator']);
        $this->assertInstanceOf(MigrateCommand::class, $this->app['command.mongolid-migrate']);
        $this->assertInstanceOf(FreshCommand::class, $this->app['command.mongolid-migrate.fresh']);
        $this->assertInstanceOf(InstallCommand::class, $this->app['command.mongolid-migrate.install']);
        $this->assertInstanceOf(MigrateMakeCommand::class, $this->app['command.mongolid-migrate.make']);
        $this->assertInstanceOf(RefreshCommand::class, $this->app['command.mongolid-migrate.refresh']);
        $this->assertInstanceOf(ResetCommand::class, $this->app['command.mongolid-migrate.reset']);
        $this->assertInstanceOf(RollbackCommand::class, $this->app['command.mongolid-migrate.rollback']);
        $this->assertInstanceOf(StatusCommand::class, $this->app['command.mongolid-migrate.status']);
    }

    public function testProvides()
    {
        // Set
        $provider = new MigrationServiceProvider($this->app);
        $expected = [
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

        // Actions
        $result = $provider->provides();

        // Assertions
        $this->assertSame($expected, $result);
    }
}
