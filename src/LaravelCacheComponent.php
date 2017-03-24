<?php

namespace MongolidLaravel;

use Illuminate\Cache\Repository;
use Mongolid\Util\CacheComponentInterface;
use stdClass;

/**
 * Wraps the Laravel's event Dispatcher in order to trigger Mongolid events.
 */
class LaravelCacheComponent implements CacheComponentInterface
{
    /**
     * Injects the dependencies of LaravelCacheComponent.
     *
     * @param Repository $laravelCache Cache component that will be used to store.
     */
    public function __construct(Repository $laravelCache)
    {
        $this->laravelCache = $laravelCache;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key Cache key of the item to be retrieved.
     *
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->laravelCache->get($key, null);
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key     Cache key of the item.
     * @param mixed  $value   Value being stored in cache.
     * @param float  $minutes Cache ttl.
     *
     * @return void
     */
    public function put(string $key, $value, float $minutes)
    {
        if (is_array($value)) {
            foreach ($value as $index => $document) {
                if ($document instanceof stdClass) {
                    $value[$index] = (array) $document;
                }
            }
        }

        $this->laravelCache->put($key, $value, $minutes);
    }

    /**
     * Determine if an item exists in the cache. This method will also check
     * if the ttl of the given cache key has been expired and will free the
     * memory if so.
     *
     * @param string $key Cache key of the item.
     *
     * @return bool Has cache key.
     */
    public function has(string $key): bool
    {
        return $this->laravelCache->has($key);
    }
}
