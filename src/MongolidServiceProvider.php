<?php

namespace MongolidLaravel;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Support\ServiceProvider;
use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc as MongolidIoc;
use Mongolid\Event\EventTriggerService;
use Mongolid\Util\CacheComponentInterface;

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
    }

    /**
     * Register MongoDbConnector within the application.
     */
    public function registerConnector()
    {
        MongolidIoc::setContainer($this->app);

        $config = $this->app['config']->get('database.mongodb.default') ?? [];

        $connectionString = $this->buildConnectionString($config);
        $options = $config['options'] ?? [];
        $driverOptions = $config['driver_options'] ?? [];

        $connection = new Connection($connectionString, $options, $driverOptions);
        $connection->defaultDatabase = $config['database'] ?? 'mongolid';

        $pool = new Pool();
        $pool->addConnection($connection);

        $eventService = new EventTriggerService();
        $eventService->registerEventDispatcher($this->app->make(LaravelEventTrigger::class));

        $this->app->instance(Pool::class, $pool);
        $this->app->instance(EventTriggerService::class, $eventService);
        $this->app->bind(CacheComponentInterface::class, function ($app) {
            return new LaravelCacheComponent($app[CacheRepository::class]);
        });
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
                    $app['hash'], $config['model']
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
     * @return MongolidFailedJobProvider
     */
    private function buildMongolidFailedJobProvider($app, $collection)
    {
        return new MongolidFailedJobProvider(
            $app->makeWith(FailedJobsService::class, compact('collection'))
        );
    }
}
