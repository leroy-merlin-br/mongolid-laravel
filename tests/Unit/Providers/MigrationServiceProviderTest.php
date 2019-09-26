<?php
namespace Mongolid\Laravel\Providers;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Mockery as m;
use Mongolid\Laravel\Migrations\Commands\FreshCommand;
use Mongolid\Laravel\Migrations\Commands\InstallCommand;
use Mongolid\Laravel\Migrations\Commands\MigrateCommand;
use Mongolid\Laravel\Migrations\Commands\MigrateMakeCommand;
use Mongolid\Laravel\Migrations\Commands\RefreshCommand;
use Mongolid\Laravel\Migrations\Commands\ResetCommand;
use Mongolid\Laravel\Migrations\Commands\RollbackCommand;
use Mongolid\Laravel\Migrations\Commands\StatusCommand;
use Mongolid\Laravel\Migrations\MigrationCreator;
use Mongolid\Laravel\Migrations\Migrator;
use Mongolid\Laravel\Migrations\MongolidMigrationRepository;
use Mongolid\Laravel\TestCase;

class MigrationServiceProviderTest extends TestCase
{
    public function testShouldBoot(): void
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

    public function testShouldRegister(): void
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

    public function testProvides(): void
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
