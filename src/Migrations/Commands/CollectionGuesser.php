<?php
namespace MongolidLaravel\Migrations\Commands;

class CollectionGuesser
{
    /**
     * Attempt to guess the collection name and "creation" status of the given migration.
     *
     * @param  string  $migration
     * @return array
     */
    public static function guess($migration)
    {
        if (preg_match('/^create_(\w+)_collection$/', $migration, $matches)) {
            return [$matches[1], $create = true];
        }

        if (preg_match('/_(to|from|in)_(\w+)_collection$/', $migration, $matches)) {
            return [$matches[2], $create = false];
        }
    }
}
