<?php
namespace Mongolid\Laravel\Migrations;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Illuminate\Console\OutputStyle;
use Illuminate\Filesystem\Filesystem;
use Mockery as m;
use Mongolid\Laravel\TestCase;

class MigratorTest extends TestCase
{
    public function testShouldRunWithNoPendingMigrations(): void
    {
        // Set
        $repository = m::mock(MigrationRepositoryInterface::class);
        $files = m::mock(Filesystem::class);
        $migrator = new Migrator($repository, $files);

        $paths = ['/database'];
        $options = [];
        $expected = [];

        $ran = ['2018_12_12_123456_create_users_index', '2018_12_12_123456_drop_admin'];
        $migrationFiles = [
            '2018_12_12_123456_create_users_index' => 'database/migrations/2018_12_12_123456_create_users_index.php',
            '2018_12_12_123456_drop_admin' => 'database/migrations/2018_12_12_123456_drop_admin.php',
        ];

        // Expectations
        $repository->expects()
            ->getRan()
            ->andReturn($ran);

        $files->expects()
            ->glob('/database/*_*.php')
            ->andReturn($migrationFiles);

        $repository->expects()
            ->getNextBatchNumber()
            ->never();

        // Actions
        $result = $migrator->run($paths, $options);

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldRunWithPendingMigrations(): void
    {
        // Set
        $repository = m::mock(MigrationRepositoryInterface::class);
        $files = m::mock(Filesystem::class);
        $migrator = new Migrator($repository, $files);
        $output = m::mock(OutputStyle::class);
        $migrator->setOutput($output);

        $paths = ['/database'];
        $options = [];
        $expected = ['database/migrations/2018_12_12_123456_drop_admin.php'];

        $ran = ['2018_12_12_123456_create_users_index'];
        $migrationFiles = [
            '2018_12_12_123456_create_users_index' => 'database/migrations/2018_12_12_123456_create_users_index.php',
            '2018_12_12_123456_drop_admin' => 'database/migrations/2018_12_12_123456_drop_admin.php',
        ];

        // Expectations
        $repository->expects()
            ->getRan()
            ->andReturn($ran);

        $files->expects()
            ->glob('/database/*_*.php')
            ->andReturn($migrationFiles);

        $files->expects()
            ->requireOnce('database/migrations/2018_12_12_123456_drop_admin.php');

        $repository->expects()
            ->getNextBatchNumber()
            ->andReturn(2);

        $repository->expects()
            ->log('2018_12_12_123456_drop_admin', 2);

        $output->allows()
            ->writeln()
            ->withAnyArgs();

        // Actions
        $result = $migrator->run($paths, $options);

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldRollbackWithNoMigrations(): void
    {
        // Set
        $repository = m::mock(MigrationRepositoryInterface::class);
        $files = m::mock(Filesystem::class);
        $migrator = new Migrator($repository, $files);

        $paths = ['/database'];
        $options = [];
        $expected = [];

        $last = [];

        // Expectations
        $repository->expects()
            ->getLast()
            ->andReturn($last);

        // Actions
        $result = $migrator->rollback($paths, $options);

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldRollbackWithMigrations(): void
    {
        // Set
        $repository = m::mock(MigrationRepositoryInterface::class);
        $files = m::mock(Filesystem::class);
        $migrator = new Migrator($repository, $files);

        $paths = ['/database'];
        $options = ['step' => 9];
        $expected = ['database/migrations/2018_12_12_123456_drop_admin.php'];

        $last = [
            (object) ['batch' => 9, 'migration' => '2018_12_12_123456_drop_admin'],
            (object) ['batch' => 9, 'migration' => 'not_found'],
        ];
        $migrationFiles = [
            '2018_12_12_123456_drop_admin' => 'database/migrations/2018_12_12_123456_drop_admin.php',
        ];

        // Expectations
        $repository->expects()
            ->getMigrations(9)
            ->andReturn($last);

        $files->expects()
            ->glob('/database/*_*.php')
            ->andReturn($migrationFiles);

        $files->expects()
            ->requireOnce('database/migrations/2018_12_12_123456_drop_admin.php');

        $repository->expects()
            ->delete($last[0]);

        // Actions
        $result = $migrator->rollback($paths, $options);

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldResetWithNoMigrations(): void
    {
        // Set
        $repository = m::mock(MigrationRepositoryInterface::class);
        $files = m::mock(Filesystem::class);
        $migrator = new Migrator($repository, $files);

        $paths = ['/database'];
        $expected = [];

        $ran = [];

        // Expectations
        $repository->expects()
            ->getRan()
            ->andReturn($ran);

        // Actions
        $result = $migrator->reset($paths);

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldResetWithMigrations(): void
    {
        // Set
        $repository = m::mock(MigrationRepositoryInterface::class);
        $files = m::mock(Filesystem::class);
        $migrator = new Migrator($repository, $files);

        $paths = ['/database'];
        $expected = ['database/migrations/2018_12_12_123456_migration_creator_fake_migration.php'];

        $ran = ['2018_12_12_123456_migration_creator_fake_migration'];
        $migrationFiles = [
            '2018_12_12_123456_migration_creator_fake_migration' => 'database/migrations/2018_12_12_123456_migration_creator_fake_migration.php',
            '2018_12_12_123456_drop_admin' => 'database/migrations/2018_12_12_123456_drop_admin.php',
        ];

        // Expectations
        $repository->expects()
            ->getRan()
            ->andReturn($ran);

        $files->expects()
            ->glob('/database/*_*.php')
            ->andReturn($migrationFiles);

        $files->expects()
            ->requireOnce('database/migrations/2018_12_12_123456_migration_creator_fake_migration.php');

        $files->expects()
            ->requireOnce('database/migrations/2018_12_12_123456_drop_admin.php');

        $repository->expects()
            ->delete(
                m::on(
                    function ($parameter) use ($ran) {
                        $this->assertEquals((object) ['migration' => $ran[0]], $parameter);

                        return true;
                    }
                )
            );

        // Actions
        $result = $migrator->reset($paths);

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldGetPaths(): void
    {
        // Set
        $repository = m::mock(MigrationRepositoryInterface::class);
        $files = m::mock(Filesystem::class);
        $migrator = new Migrator($repository, $files);

        // Actions
        $migrator->path('/database');
        $result = $migrator->paths();

        // Assertions
        $this->assertSame(['/database'], $result);
    }

    public function testShouldGetConnection(): void
    {
        // Set
        $repository = m::mock(MigrationRepositoryInterface::class);
        $files = m::mock(Filesystem::class);
        $migrator = new Migrator($repository, $files);

        // Expectations
        $repository->expects()
            ->setSource('testing');

        // Actions
        $migrator->setConnection('testing');
        $result = $migrator->getConnection();

        // Assertions
        $this->assertSame('testing', $result);
    }

    public function testShouldGetRepository(): void
    {
        // Set
        $repository = m::mock(MigrationRepositoryInterface::class);
        $files = m::mock(Filesystem::class);
        $migrator = new Migrator($repository, $files);

        // Actions
        $result = $migrator->getRepository();

        // Assertions
        $this->assertSame($repository, $result);
    }

    public function testRepositoryShouldExist(): void
    {
        // Set
        $repository = m::mock(MigrationRepositoryInterface::class);
        $files = m::mock(Filesystem::class);
        $migrator = new Migrator($repository, $files);

        // Expectations
        $repository->expects()
            ->repositoryExists()
            ->andReturn(true);

        // Actions
        $result = $migrator->repositoryExists();

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldGetFilesystem(): void
    {
        // Set
        $repository = m::mock(MigrationRepositoryInterface::class);
        $files = m::mock(Filesystem::class);
        $migrator = new Migrator($repository, $files);

        // Actions
        $result = $migrator->getFilesystem();

        // Assertions
        $this->assertSame($files, $result);
    }
}
