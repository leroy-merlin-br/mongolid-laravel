<?php namespace Zizaco\MongolidLaravel;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Guard;

class MongoLidAuthManager extends AuthManager {

    /**
     * Create an instance of the MongoLid driver.
     *
     * @return \Illuminate\Auth\Guard
     */
    public function createMongoLidDriver()
    {
        $provider = $this->createMongoLidProvider();

        return new Guard($provider, $this->app['session']);
    }

    /**
     * Create an instance of the MongoLid user provider.
     *
     * @return \Illuminate\Auth\MongoLidUserProvider
     */
    protected function createMongoLidProvider()
    {
        $model = $this->app['config']['auth.model'];

        return new MongoLidUserProvider($this->app['hash'], $model);
    }
}
