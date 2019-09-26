<?php
namespace Mongolid\Laravel\Migrations\Commands;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Illuminate\Foundation\Application;
use Illuminate\Support\Composer;
use Mockery as m;
use Mongolid\Laravel\Migrations\MigrationCreator;
use Mongolid\Laravel\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class MigrateMakeCommandTest extends TestCase
{
    public function testBasicCreateDumpsAutoload(): void
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

    public function testBasicCreateGivesCreatorProperArguments(): void
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

    public function testBasicCreateGivesCreatorProperArgumentsWhenNameIsStudlyCase(): void
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

    public function testBasicCreateGivesCreatorProperArgumentsWhenCollectionIsSet(): void
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

    public function testBasicCreateGivesCreatorProperArgumentsWhenCreateCollectionPatternIsFound(): void
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

    public function testCanSpecifyPathToCreateMigrationsIn(): void
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
