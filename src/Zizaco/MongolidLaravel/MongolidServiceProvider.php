<?php
namespace Zizaco\MongolidLaravel;

use Illuminate\Support\ServiceProvider;
use Zizaco\Mongolid\MongoDbConnector;
use Zizaco\Mongolid\Model;

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
        $this->extendsAuthManager();

        MongoLid::setEventDispatcher($this->app['events']);
    }

    /**
     * Register MongoDbConnector within the application
     *
     * @return void
     */
    public function registerConnector()
    {
        $connectionString = $this->buildConnectionString();

        $connection = new MongoDbConnector;
        $connection->getConnection($connectionString);

        $this->app['MongoLidConnector'] = $this->app->share(
            function ($app) use ($connection) {
                return $connection;
            }
        );
    }

    /**
     * Registers mongoLid Driver in AuthManager
     *
     * @return void
     */
    public function extendsAuthManager()
    {
        // MongoLid Auth Driver
        $this->app['auth']->extend(
            'mongoLid',
            function ($app) {
                $provider =  new MongoLidUserProvider($app['hash'], $app['config']->get('auth.model'));

                return new \Illuminate\Auth\Guard($provider, $app['session.store']);
            }
        );
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
