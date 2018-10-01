<?php

namespace MongolidLaravel\Migrations\Commands;

use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Application;
use Mockery as m;
use MongolidLaravel\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class RefreshCommandTest extends TestCase
{
    public function testRefreshCommandCallsCommandsWithProperArguments()
    {
        // Set
        $command = new RefreshCommand();

        $app = m::mock(Application::class.'[environment]');
        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $resetCommand = m::mock(ResetCommand::class);
        $migrateCommand = m::mock(MigrateCommand::class);

        $quote = DIRECTORY_SEPARATOR == '\\' ? '"' : "'";

        // Expectations
        $app->expects()
            ->environment()
            ->andReturn('development');

        $console->expects()
            ->find('migrate:reset')
            ->andReturn($resetCommand);

        $console->expects()
            ->find('migrate')
            ->andReturn($migrateCommand);

        $resetCommand->expects()
            ->run(
                new InputMatcher("--database --path --realpath --force {$quote}migrate:reset{$quote}"),
                m::type(OutputStyle::class)
            );

        $migrateCommand->expects()
            ->run(
                new InputMatcher('--database --path --realpath --force migrate'),
                m::type(OutputStyle::class)
            );

        // Actions
        $command->run(new ArrayInput([]), new NullOutput());
    }

    public function testRefreshCommandCallsCommandsWithStep()
    {
        // Set
        $command = new RefreshCommand();

        $app = m::mock(Application::class.'[environment]');
        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $rollbackCommand = m::mock(RollbackCommand::class);
        $migrateCommand = m::mock(MigrateCommand::class);

        $quote = DIRECTORY_SEPARATOR == '\\' ? '"' : "'";

        // Expectations
        $app->expects()
            ->environment()
            ->andReturn('development');

        $console->expects()
            ->find('migrate:rollback')
            ->andReturn($rollbackCommand);

        $console->expects()
            ->find('migrate')
            ->andReturn($migrateCommand);

        $rollbackCommand->expects()
            ->run(
                new InputMatcher("--database --path --realpath --step=2 --force {$quote}migrate:rollback{$quote}"),
                m::type(OutputStyle::class)
            );

        $migrateCommand->expects()
            ->run(
                new InputMatcher('--database --path --realpath --force migrate'),
                m::type(OutputStyle::class)
            );

        // Actions
        $command->run(new ArrayInput(['--step' => 2]), new NullOutput());
    }
}

class InputMatcher extends m\Matcher\MatcherAbstract
{
    /**
     * @param ArrayInput $actual
     *
     * @return bool
     */
    public function match(&$actual)
    {
        return (string) $actual == $this->_expected;
    }

    public function __toString()
    {
        return '';
    }
}
