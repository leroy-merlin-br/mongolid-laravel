<?php
namespace Mongolid\Laravel\Migrations\Commands;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Mongolid\Connection\Connection;
use Symfony\Component\Console\Input\InputOption;

class FreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mongolid-migrate:fresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop database and re-run all migrations';

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $database = $this->input->getOption('database');

        $this->dropDatabase($database);

        $this->info('Dropped database successfully.');

        $this->call(
            'mongolid-migrate',
            [
                '--database' => $database,
                '--path' => $this->input->getOption('path'),
                '--realpath' => $this->input->getOption('realpath'),
                '--force' => true,
            ]
        );

        if ($this->needsSeeding()) {
            $this->runSeeder($database);
        }
    }

    /**
     * Drop all of the database collections.
     *
     * @param string $database
     */
    protected function dropDatabase($database)
    {
        $database = $database ?? $this->connection->defaultDatabase;

        $this->connection->getClient()->dropDatabase($database);
    }

    /**
     * Determine if the developer has requested database seeding.
     *
     * @return bool
     */
    protected function needsSeeding()
    {
        return $this->option('seed') || $this->option('seeder');
    }

    /**
     * Run the database seeder command.
     *
     * @param string $database
     */
    protected function runSeeder($database)
    {
        $this->call(
            'db:seed',
            [
                '--database' => $database,
                '--class' => $this->option('seeder') ?: 'DatabaseSeeder',
                '--force' => $this->option('force'),
            ]
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],

            ['path', null, InputOption::VALUE_OPTIONAL, 'The path to the migrations files to be executed'],

            [
                'realpath',
                null,
                InputOption::VALUE_NONE,
                'Indicate any provided migration file paths are pre-resolved absolute paths',
            ],

            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run'],

            ['seeder', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder'],
        ];
    }
}
