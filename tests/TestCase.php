<?php

class TestCase extends Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \MongolidLaravel\MongolidServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'MongoLid'  => \MongolidLaravel\MongolidModel::class,
            'Validator' => \Illuminate\Support\Facades\Validator::class,
        ];
    }
}
