<?php
namespace MongolidLaravel\Migrations\Commands;

use Illuminate\Foundation\Application;
use Illuminate\Support\Composer;
use Mockery as m;
use MongolidLaravel\Migrations\MigrationCreator;
use MongolidLaravel\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class MigrateMakeCommandTest extends TestCase
{
    public function testBasicCreateDumpsAutoload()
    {
        // Set
        $creator = m::mock(MigrationCreator::class);
        $composer = m::mock(Composer::class);
        $command = new MigrateMakeCommand($creator, $composer);
        $app = new Application();
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $creator->expects()
            ->create('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations');

        $composer->expects()
            ->dumpAutoloads();

        // Actions
        $command->run(new ArrayInput(['name' => 'create_foo']), new NullOutput());
    }

    public function testBasicCreateGivesCreatorProperArguments()
    {
        // Set
        $creator = m::mock(MigrationCreator::class);
        $composer = m::mock(Composer::class);
        $command = new MigrateMakeCommand($creator, $composer);
        $app = new Application();
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $creator->expects()
            ->create('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations');

        $composer->expects()
            ->dumpAutoloads();

        // Actions
        $command->run(new ArrayInput(['name' => 'create_foo']), new NullOutput());
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenNameIsStudlyCase()
    {
        // Set
        $creator = m::mock(MigrationCreator::class);
        $composer = m::mock(Composer::class);
        $command = new MigrateMakeCommand($creator, $composer);
        $app = new Application();
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $creator->expects()
            ->create('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations');

        $composer->expects()
            ->dumpAutoloads();

        // Actions
        $command->run(new ArrayInput(['name' => 'CreateFoo']), new NullOutput());
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenCollectionIsSet()
    {
        // Set
        $creator = m::mock(MigrationCreator::class);
        $composer = m::mock(Composer::class);
        $command = new MigrateMakeCommand($creator, $composer);
        $app = new Application();
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $creator->expects()
            ->create('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations');

        $composer->expects()
            ->dumpAutoloads();

        // Actions
        $command->run(new ArrayInput(['name' => 'create_foo']), new NullOutput());
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenCreateCollectionPatternIsFound()
    {
        // Set
        $creator = m::mock(MigrationCreator::class);
        $composer = m::mock(Composer::class);
        $command = new MigrateMakeCommand($creator, $composer);
        $app = new Application();
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $creator->expects()
            ->create(
                'create_users_collection',
                __DIR__.DIRECTORY_SEPARATOR.'migrations'
            );

        $composer->expects()
            ->dumpAutoloads();

        // Actions
        $command->run(new ArrayInput(['name' => 'create_users_collection']), new NullOutput());
    }

    public function testCanSpecifyPathToCreateMigrationsIn()
    {
        // Set
        $creator = m::mock(MigrationCreator::class);
        $composer = m::mock(Composer::class);
        $command = new MigrateMakeCommand($creator, $composer);
        $app = new Application();
        $command->setLaravel($app);
        $app->setBasePath('/home/laravel');

        // Expectations
        $creator->expects()
            ->create(
                'create_foo',
                '/home/laravel/vendor/laravel-package/migrations'
            );

        $composer->expects()
            ->dumpAutoloads();

        // Actions
        $command->run(
            new ArrayInput(['name' => 'create_foo', '--path' => 'vendor/laravel-package/migrations']),
            new NullOutput()
        );
    }
}
