<?php

namespace MongolidLaravel;

use Illuminate\Cache\Repository;
use Mongolid\Util\CacheComponentInterface;
use stdClass;

/**
 * Wraps the Laravel's Cache Repository to implement Mongolid Interface.
 * Add a second layer of cache in memory to avoid hitting
 * Laravel's cache twice for large results.
 */
class LaravelCacheComponent implements CacheComponentInterface
{
    /**
     * Copy cache result in memory array.
     *
     * @var mixed[]
     */
    private $inMemoryCache = [];

    /**
     * Injects the dependencies of LaravelCacheComponent.
     *
     * @param Repository $laravelCache cache component that will be used to store
     */
    public function __construct(Repository $laravelCache)
    {
        $this->laravelCache = $laravelCache;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key cache key of the item to be retrieved
     *
     * @return mixed
     */
    public function get(string $key)
    {
        if (isset($this->inMemoryCache[$key])) {
            return $this->inMemoryCache[$key];
        }

        return $this->inMemoryCache[$key] = $this->laravelCache->get($key, null);
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key     cache key of the item
     * @param mixed  $value   value being stored in cache
     * @param float  $minutes cache ttl
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
     * @param string $key cache key of the item
     *
     * @return bool has cache key
     */
    public function has(string $key): bool
    {
        return $this->laravelCache->has($key);
    }
}
