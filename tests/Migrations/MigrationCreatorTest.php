<?php
namespace MongolidLaravel\Migrations;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Mockery as m;
use MongolidLaravel\TestCase;

class MigrationCreatorTest extends TestCase
{
    public function testBasicCreateMethodStoresMigrationFile()
    {
        // Set
        $files = m::mock(Filesystem::class);
        $creator = m::mock(MigrationCreator::class.'[getDatePrefix]', [$files]);
        $creator->shouldAllowMockingProtectedMethods();

        // Expectations
        $creator->expects()
            ->getDatePrefix()
            ->andReturn('foo');

        $files->expects()
            ->get($creator->stubPath().'/blank.stub')
            ->andReturn('DummyClass');

        $files->expects()
            ->put('foo/foo_create_bar.php', 'CreateBar');

        // Actions
        $creator->create('create_bar', 'foo');
    }

    public function testBasicCreateMethodCallsPostCreateHooks()
    {
        // Set
        $files = m::mock(Filesystem::class);
        $creator = m::mock(MigrationCreator::class.'[getDatePrefix]', [$files]);
        $creator->shouldAllowMockingProtectedMethods();

        $hasRun = false;
        $creator->afterCreate(
            function () use (&$hasRun) {
                $hasRun = true;
            }
        );

        // Expectations
        $creator->expects()
            ->getDatePrefix()
            ->andReturn('foo');

        $files->expects()
            ->get($creator->stubPath().'/blank.stub')->andReturn('DummyClass');

        $files->expects()
            ->put('foo/foo_create_bar.php', 'CreateBar');

        // Actions
        $creator->create('create_bar', 'foo');

        // Assertions
        $this->assertTrue($hasRun);
    }

    public function testCollectionUpdateMigrationStoresMigrationFile()
    {
        // Set
        $files = m::mock(Filesystem::class);
        $creator = m::mock(MigrationCreator::class.'[getDatePrefix]', [$files]);
        $creator->shouldAllowMockingProtectedMethods();

        // Expectations
        $creator->expects()
            ->getDatePrefix()
            ->andReturn('foo');

        $files->expects()
            ->get($creator->stubPath().'/blank.stub')
            ->andReturn('DummyClass');

        $files->expects()
            ->put('foo/foo_create_bar.php', 'CreateBar');

        // Actions
        $creator->create('create_bar', 'foo');
    }

    public function testCollectionCreationMigrationStoresMigrationFile()
    {
        // Set
        $files = m::mock(Filesystem::class);
        $creator = m::mock(MigrationCreator::class.'[getDatePrefix]', [$files]);
        $creator->shouldAllowMockingProtectedMethods();

        // Expectations
        $creator->expects()
            ->getDatePrefix()
            ->andReturn('foo');

        $files->expects()
            ->get($creator->stubPath().'/blank.stub')
            ->andReturn('DummyClass');

        $files->expects()
            ->put('foo/foo_create_bar.php', 'CreateBar');

        // Actions
        $creator->create('create_bar', 'foo');
    }

    public function testCollectionUpdateMigrationWontCreateDuplicateClass()
    {
        // Set
        $files = m::mock(Filesystem::class);
        $creator = m::mock(MigrationCreator::class.'[getDatePrefix]', [$files]);
        $creator->shouldAllowMockingProtectedMethods();

        // Expectations
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A MigrationCreatorFakeMigration class already exists.');

        // Actions
        $creator->create('migration_creator_fake_migration', 'foo');
    }
}
