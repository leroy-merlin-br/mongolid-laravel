<?php namespace Zizaco\MongolidLaravel;

use Illuminate\Support\ServiceProvider;
use Zizaco\Mongolid\MongoDbConnector;
use Zizaco\Mongolid\Model;

class MongolidServiceProvider extends ServiceProvider {

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
        $config = $this->app->make('config');

        $connectionString = 'mongodb://'.
            $config->get('database.mongodb.default.host', '127.0.0.1').
            ':'.
            $config->get('database.mongodb.default.port', 27017).
            '/'.
            $config->get('database.mongodb.default.database', 'mongolid');

        $connection = new MongoDbConnector;
        $connection->getConnection( $connectionString );

        $this->app['MongoLidConnector'] = $this->app->share(function($app) use ($connection)
        {
            return $connection;
        });
    }
}
