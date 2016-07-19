<?php

use Mongolid\Container\Ioc;

class TestCase extends Orchestra\Testbench\TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        Ioc::setContainer($this->app);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            \MongolidLaravel\MongolidServiceProvider::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageAliases($app)
    {
        return [
            'MongoLid'  => \MongolidLaravel\MongolidModel::class,
            'Validator' => \Illuminate\Support\Facades\Validator::class,
        ];
    }
}
