<?php
namespace MongolidLaravel\Migrations;

use Illuminate\Filesystem\Filesystem;
use Mockery as m;
use MongolidLaravel\TestCase;

class MigrationCreatorTest extends TestCase
{
    public function testBasicCreateMethodStoresMigrationFile()
    {
        $creator = $this->getCreator();

        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/blank.stub')->andReturn('DummyClass');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar');

        $creator->create('create_bar', 'foo');
    }

    public function testBasicCreateMethodCallsPostCreateHooks()
    {
        $collection = 'baz';

        $creator = $this->getCreator();
        unset($_SERVER['__migration.creator']);
        $creator->afterCreate(function ($collection) {
            $_SERVER['__migration.creator'] = $collection;
        });

        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/update.stub')->andReturn('DummyClass DummyCollection');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');

        $creator->create('create_bar', 'foo', $collection);

        $this->assertEquals($_SERVER['__migration.creator'], $collection);

        unset($_SERVER['__migration.creator']);
    }

    public function testCollectionUpdateMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/update.stub')->andReturn('DummyClass DummyCollection');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');

        $creator->create('create_bar', 'foo', 'baz');
    }

    public function testCollectionCreationMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/create.stub')->andReturn('DummyClass DummyCollection');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');

        $creator->create('create_bar', 'foo', 'baz', true);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A MigrationCreatorFakeMigration class already exists.
     */
    public function testCollectionUpdateMigrationWontCreateDuplicateClass()
    {
        $creator = $this->getCreator();

        $creator->create('migration_creator_fake_migration', 'foo');
    }

    protected function getCreator()
    {
        $files = m::mock(Filesystem::class);

        return $this->getMockBuilder(MigrationCreator::class)->setMethods(['getDatePrefix'])->setConstructorArgs([$files])->getMock();
    }
}
