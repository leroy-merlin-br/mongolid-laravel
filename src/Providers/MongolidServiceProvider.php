<?php
namespace Mongolid\Laravel\Providers;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Support\ServiceProvider;
use Mongolid\Connection\Connection;
use Mongolid\Container\Container;
use Mongolid\Event\EventTriggerService;
use Mongolid\Laravel\EventTrigger;
use Mongolid\Laravel\FailedJobsService;
use Mongolid\Laravel\Validation\Rules;

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
     */
    public function register()
    {
        $this->registerConnector();
    }

    /**
     * Register the mongoLid driver in auth AuthManager.
     */
    public function boot()
    {
        $this->extendsAuthManager();

        $this->replaceQueueFailer();

        $this->createValidationRules();
    }

    /**
     * Register MongoDbConnector within the application.
     */
    public function registerConnector()
    {
        Container::setContainer($this->app);

        $this->app->singleton(
            Connection::class,
            function ($app) {
                $config = $app['config']->get('database.mongodb.default') ?? [];
                $connectionString = $this->buildConnectionString($config);
                $options = $config['options'] ?? [];
                $driverOptions = $config['driver_options'] ?? [];

                $connection = new Connection($connectionString, $options, $driverOptions);
                $connection->defaultDatabase = $config['database'] ?? 'mongolid';

                return $connection;
            }
        );
        $this->app->singleton(
            EventTriggerService::class,
            function ($app) {
                $eventService = new EventTriggerService();
                $eventService->registerEventDispatcher($app->make(EventTrigger::class));

                return $eventService;
            }
        );
    }

    /**
     * Registers mongoLid Driver in AuthManager.
     */
    public function extendsAuthManager()
    {
        $this->app['auth']->provider(
            'mongolid',
            function ($app, array $config) {
                return new MongolidUserProvider(
                    $app['hash'],
                    $config['model']
                );
            }
        );
    }

    /**
     * Builds the connection string based in the laravel's config/database.php
     * config file.
     *
     * @param array $config Config to build connection string
     *
     * @return string The connection string
     */
    protected function buildConnectionString(array $config): string
    {
        if (isset($config['connection_string'])) {
            return $config['connection_string'];
        }

        $result = 'mongodb://';

        // If username is present, append "<username>:<password>@"
        if (isset($config['username'])) {
            $result .= sprintf(
                '%s:%s@',
                $config['username'],
                $config['password'] ?? ''
            );
        }

        // Append "<hostname>/<database>"
        $result .= sprintf(
            '%s/%s',
            $this->buildHostname($config),
            $config['database'] ?? 'mongolid'
        );

        if (isset($config['cluster']['replica_set'])) {
            $result .= '?replicaSet='.$config['cluster']['replica_set'];
        }

        return $result;
    }

    /**
     * Build connection string hostname part in <host>:<port>
     * format or <host>:<port>,<host>:<port> in case of
     * cluster configuration.
     *
     * @param array $config Config to build hostname
     *
     * @return string Hostname string
     */
    private function buildHostname(array $config): string
    {
        if (isset($config['cluster'])) {
            foreach ($config['cluster']['nodes'] as $node) {
                $nodes[] = sprintf(
                    '%s:%s',
                    $node['host'] ?? '127.0.0.1',
                    $node['port'] ?? 27017
                );
            }

            return implode(',', $nodes ?? ['127.0.0.1:27017']);
        }

        return sprintf(
            '%s:%s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 27017
        );
    }

    /**
     * Rebind Laravel Failed Queue Job Provider to use Mongolid.
     */
    private function replaceQueueFailer()
    {
        $this->app->extend(
            'queue.failer',
            function ($concrete, $app) {
                $collection = $app['config']['queue.failed.collection'];

                return isset($collection)
                    ? $this->buildMongolidFailedJobProvider($app, $collection)
                    : new NullFailedJobProvider();
            }
        );
    }

    /**
     * Build Mongolid Failed Job Provider.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param string                                       $collection
     *
     * @return FailedJobProvider
     */
    private function buildMongolidFailedJobProvider($app, $collection)
    {
        return new FailedJobProvider(
            $app->makeWith(FailedJobsService::class, compact('collection'))
        );
    }

    private function createValidationRules(): void
    {
        $validator = $this->app->make(Factory::class);

        $validator->extend('mongolid_unique', Rules::class.'@unique');
        $validator->replacer('mongolid_unique', Rules::class.'@message');

        $validator->extend('mongolid_exists', Rules::class.'@exists');
        $validator->replacer('mongolid_exists', Rules::class.'@message');

        $validator->extend('object_id', Rules::class.'@objectId');
        $validator->replacer('object_id', Rules::class.'@objectIdMessage');
    }
}
