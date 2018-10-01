<?php
/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

namespace MongolidLaravel\Migrations\Commands;

use Illuminate\Console\Command;
use MongolidLaravel\Migrations\MigrationRepositoryInterface;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mongolid-migrate:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the migration repository';

    /**
     * The repository instance.
     *
     * @var \MongolidLaravel\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * Create a new migration install command instance.
     *
     * @param \MongolidLaravel\Migrations\MigrationRepositoryInterface $repository
     */
    public function __construct(MigrationRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->repository->setSource($this->input->getOption('database'));

        $this->repository->createRepository();

        $this->info('Migration collection created successfully.');
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
        ];
    }
}
