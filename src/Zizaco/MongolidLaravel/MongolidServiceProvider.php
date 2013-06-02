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
        $this->registerConnector();

        $this->registerAuthManager();
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
        $connection->getConnection( $connectionString );

        $this->app['MongoLidConnector'] = $this->app->share(function($app) use ($connection)
        {
            return $connection;
        });
    }

    /**
     * Registers Mongo Auth Manager
     * 
     * @return void
     */
    public function registerAuthManager()
    {
        // MongoLid Auth Manager
        $this->app['auth'] = $this->app->share(function($app)
        {
            // Once the authentication service has actually been requested by the developer
            // we will set a variable in the application indicating such. This helps us
            // know that we need to set any queued cookies in the after event later.
            $app['auth.loaded'] = true;

            return new MongoLidAuthManager($app);
        });
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

        // Connection string should begin with "mongodb://"
        $result = 'mongodb://';
        
        // If username is present, append "<username>:<password>@"
        if ($config->get('database.mongodb.default.username', '' ))
        {
            $result .=
                $config->get('database.mongodb.default.username', '').':'.
                $config->get('database.mongodb.default.password', '').'@';
        }
        
        // Append "<host>:<port>/<database>"
        $result .=
            $config->get('database.mongodb.default.host', '127.0.0.1').':'.
            $config->get('database.mongodb.default.port', 27017).'/'.
            $config->get('database.mongodb.default.database', 'mongolid');

        return $result;
    }
}
