<?php
namespace Mongolid\Laravel\Migrations;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Mockery as m;
use Mongolid\Laravel\TestCase;

class MigrationCreatorTest extends TestCase
{
    public function testBasicCreateMethodStoresMigrationFile(): void
    {
        // Set
        $files = m::mock(Filesystem::class);
        $creator = new MigrationCreator($files);

        // Expectations
        $files->expects()
            ->get($creator->stubPath().'/blank.stub')
            ->andReturn('DummyClass');

        $files->expects()
            ->put(m::pattern('/database\/migrations\/\d{4}_\d{2}_\d{2}_\d{6}_create_bar.php/'), 'CreateBar');

        // Actions
        $creator->create('create_bar', 'database/migrations');
    }

    public function testBasicCreateMethodCallsPostCreateHooks(): void
    {
        // Set
        $files = m::mock(Filesystem::class);
        $creator = new MigrationCreator($files);

        $hasRun = false;
        $creator->afterCreate(
            function () use (&$hasRun) {
                $hasRun = true;
            }
        );

        // Expectations
        $files->expects()
            ->get($creator->stubPath().'/blank.stub')->andReturn('DummyClass');

        $files->expects()
            ->put(m::pattern('/database\/migrations\/\d{4}_\d{2}_\d{2}_\d{6}_add_bar_index.php/'), 'AddBarIndex');

        // Actions
        $creator->create('add_bar_index', 'database/migrations');

        // Assertions
        $this->assertTrue($hasRun);
    }

    public function testCollectionUpdateMigrationWontCreateDuplicateClass(): void
    {
        // Set
        $files = new Filesystem();
        $creator = new MigrationCreator($files);

        // Expectations
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A MigrationCreatorFakeMigration class already exists.');

        // Actions
        $creator->create('migration_creator_fake_migration', 'database/migrations');
    }

    public function testShouldGetFilesystem(): void
    {
        // Set
        $files = new Filesystem();
        $creator = new MigrationCreator($files);

        // Actions
        $result = $creator->getFilesystem();

        // Assertions
        $this->assertSame($files, $result);
    }
}
