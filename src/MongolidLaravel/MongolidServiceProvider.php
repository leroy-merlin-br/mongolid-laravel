<?php

namespace MongolidLaravel;

use Illuminate\Support\ServiceProvider;
use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc as MongolidIoc;

class MongolidServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConnector();
    }

    /**
     * Register the mongoLid driver in auth AuthManager
     *
     * @return void
     */
    public function boot()
    {
        // $this->extendsAuthManager();
    }

    /**
     * Register MongoDbConnector within the application
     *
     * @return void
     */
    public function registerConnector()
    {
        MongolidIoc::setContainer($this->app);

        $config           = $this->app->make('config');
        $connectionString = $this->buildConnectionString();
        $connection       = new Connection($connectionString);
        $pool             = new Pool;

        $pool->addConnection($connection);
        $this->app->instance(Pool::class, $pool);

        $connection->defaultDatabase = $config
            ->get('database.mongodb.default.database', 'mongolid');
    }

    /**
     * Registers mongoLid Driver in AuthManager
     *
     * @return void
     */
    public function extendsAuthManager()
    {
        // @TODO
    }

    /**
     * Builds the connection string based in the laravel's config/database.php
     * config file.
     *
     * @return string The connection string
     */
    protected function buildConnectionString()
    {
        $config = $this->app->make('config');

        if (! $result = $config->get('database.mongodb.default.connectionString')) {

            // Connection string should begin with "mongodb://"
            $result = 'mongodb://';

            // If username is present, append "<username>:<password>@"
            if ($config->get('database.mongodb.default.username')) {
                $result .=
                    $config->get('database.mongodb.default.username').':'.
                    $config->get('database.mongodb.default.password', '').'@';
            }

            // Append "<host>:<port>/<database>"
            $result .=
                $config->get('database.mongodb.default.host', '127.0.0.1').':'.
                $config->get('database.mongodb.default.port', 27017).'/'.
                $config->get('database.mongodb.default.database', 'mongolid');

        }

        return $result;
    }
}
