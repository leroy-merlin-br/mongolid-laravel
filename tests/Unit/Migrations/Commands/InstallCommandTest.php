<?php
namespace Mongolid\Laravel\Migrations\Commands;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Illuminate\Foundation\Application;
use Mockery as m;
use Mongolid\Laravel\Migrations\MigrationRepositoryInterface;
use Mongolid\Laravel\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class InstallCommandTest extends TestCase
{
    public function testFireCallsRepositoryToInstall(): void
    {
        // Set
        $repository = m::mock(MigrationRepositoryInterface::class);
        $command = new InstallCommand($repository);
        $command->setLaravel(new Application());

        // Expectations
        $repository->expects()
            ->setSource('foo');

        $repository->expects()
            ->createRepository();

        // Actions
        $command->run(new ArrayInput(['--database' => 'foo']), new NullOutput());
    }
}
