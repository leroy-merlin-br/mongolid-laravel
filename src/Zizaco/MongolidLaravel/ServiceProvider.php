<?php namespace Zizaco\MongolidLaravel;

use Illuminate\Support\ServiceProvider;
use Zizaco\Mongolid\MongoDbConnector;

class ServiceProvider extends ServiceProvider {

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

        $this->app['MongoLidConnection'] = $this->app->share(function($app)
        {
            $config = $this->app['MongoLidConnection']
            $connectionString = 'mongodb://'.
                $config->get('database.mongodb.default.host', '127.0.0.1').
                ':'.
                $config->get('database.mongodb.default.port', 27017).
                '/'.
                $config->get('database.mongodb.default.database', 'mongolid');

            $connection = new MongoDbConnector( $connectionString );

            return $connection;
        });
    }
}
