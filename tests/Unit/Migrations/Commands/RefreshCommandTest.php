<?php
namespace Mongolid\Laravel\Migrations\Commands;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Illuminate\Foundation\Application;
use Mockery as m;
use Mongolid\Laravel\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class RefreshCommandTest extends TestCase
{
    public function testRefreshCommandCallsCommandsWithProperArguments(): void
    {
        // Set
        $command = m::mock(RefreshCommand::class.'[call]');

        $app = m::mock(Application::class.'[environment]');
        $command->setLaravel($app);

        // Expectations
        $app->expects()
            ->environment()
            ->andReturn('development');

        $command->expects()
            ->call(
                'mongolid-migrate:reset',
                [
                    '--database' => null,
                    '--path' => null,
                    '--realpath' => null,
                    '--force' => false,
                ]
            );

        $command->expects()
            ->call(
                'mongolid-migrate',
                [
                    '--database' => null,
                    '--path' => null,
                    '--realpath' => null,
                    '--force' => false,
                ]
            );

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }

    public function testShouldRunRefreshCommandWithDbSeed(): void
    {
        // Set
        $command = m::mock(RefreshCommand::class.'[call]');

        $app = m::mock(Application::class.'[environment]');
        $command->setLaravel($app);

        // Expectations
        $app->expects()
            ->environment()
            ->andReturn('development');

        $command->expects()
            ->call(
                'mongolid-migrate:reset',
                [
                    '--database' => null,
                    '--path' => null,
                    '--realpath' => null,
                    '--force' => false,
                ]
            );

        $command->expects()
            ->call(
                'mongolid-migrate',
                [
                    '--database' => null,
                    '--path' => null,
                    '--realpath' => null,
                    '--force' => false,
                ]
            );

        $command->expects()
            ->call(
                'db:seed',
                [
                    '--database' => null,
                    '--class' => 'DatabaseSeeder',
                    '--force' => false,
                ]
            );

        // Actions
        $command->run(new ArrayInput(['--seed' => true]), new NullOutput());
    }

    public function testRefreshCommandCallsCommandsWithStep(): void
    {
        // Set
        $command = m::mock(RefreshCommand::class.'[call]');

        $app = m::mock(Application::class.'[environment]');
        $command->setLaravel($app);

        // Expectations
        $app->expects()
            ->environment()
            ->andReturn('development');

        $command->expects()
            ->call(
                'mongolid-migrate:rollback',
                [
                    '--database' => null,
                    '--path' => null,
                    '--realpath' => false,
                    '--step' => 2,
                    '--force' => false,
                ]
            );

        $command->expects()
            ->call(
                'mongolid-migrate',
                [
                    '--database' => null,
                    '--path' => null,
                    '--realpath' => false,
                    '--force' => false,
                ]
            );

        // Actions
        $command->run(new ArrayInput(['--step' => 2]), new NullOutput());
    }

    public function testShouldConfirmToRun(): void
    {
        // Set
        $command = m::mock(RefreshCommand::class.'[confirmToProceed]');
        $app = new Application();
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        // Expectations
        $command->expects()
            ->confirmToProceed()
            ->andReturn(false);

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }
}
