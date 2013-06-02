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
        $config = $this->app->make('config');

        $connectionString = 'mongodb://';
        
        /*
		 * DIRTY HACK
		 * The following code could be re-imagined, probably way better than I have
		 * and allows connections to MongoDB instances with authentication using
		 * a username and password
		 */
        if( $config->get('database.mongodb.default.username', '' ) != "" ) {
            $connectionString .= $config->get('database.mongodb.default.username', '').
            ':'.
            $config->get('database.mongodb.default.password', '').
            '@';
        }
        
        $connectionString .= $config->get('database.mongodb.default.host', '127.0.0.1').
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
}
