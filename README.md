# Mongolid ODM for MongoDB (Laravel Package)

<p align="center"><img src="https://user-images.githubusercontent.com/1991286/28967747-fe5c258a-78f2-11e7-91c7-8850ffb32004.png" alt="Mongolid"></p>

<p align="center">
<a href="https://travis-ci.org/leroy-merlin-br/mongolid-laravel"><img src="https://travis-ci.org/leroy-merlin-br/mongolid-laravel.svg?branch=master" alt="Build Status"></a>
<a href="https://coveralls.io/github/leroy-merlin-br/mongolid-laravel?branch=master"><img src="https://coveralls.io/repos/github/leroy-merlin-br/mongolid-laravel/badge.svg?branch=master" alt="Coverage Status"></a>
<a href="https://packagist.org/packages/leroy-merlin-br/mongolid-laravel"><img src="https://poser.pugx.org/leroy-merlin-br/mongolid-laravel/v/stable" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/leroy-merlin-br/mongolid-laravel"><img src="https://poser.pugx.org/leroy-merlin-br/mongolid-laravel/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/leroy-merlin-br/mongolid-laravel"><img src="https://poser.pugx.org/leroy-merlin-br/mongolid-laravel/license" alt="License"></a>
</p>

## About Mongolid Laravel
Easy, powerful and ultrafast ODM for PHP 7.1+ build on top of the [new mongodb driver](https://docs.mongodb.org/ecosystem/drivers/php/).

Mongolid supports **ActiveRecord** pattern.

## Introduction
Mongolid ODM (Object Document Mapper) provides a beautiful, simple implementation for working with MongoDB. Each database collection can have a corresponding "Model" which is used to interact with that collection.

> Note: The ODM implementation is within the [(non laravel) mongolid repository](https://github.com/leroy-merlin-br/mongolid).

## Requirements
- PHP **7.1** or superior
- [MongoDB Driver](http://php.net/manual/en/set.mongodb.php)
- Laravel **5.4** or superior

## Installation
You can install the library through Composer:

```
$ composer require leroy-merlin-br/mongolid-laravel
```

> **Note**: If you are using Laravel 5.5, the next steps for providers and aliases are unnecessary. MongoLid supports Laravel's [Package Discovery](https://laravel.com/docs/5.5/packages#package-discovery).

In your `config/app.php` add `'Mongolid\Laravel\Providers\MongolidServiceProvider'` to the end of the `$providers` array

```php
'providers' => [
    Illuminate\Translation\TranslationServiceProvider::class,
    Illuminate\Validation\ValidationServiceProvider::class,
    Illuminate\View\ViewServiceProvider::class,
    ...
    Mongolid\Laravel\Providers\MongolidServiceProvider::class,
],
```

(**Optional**) At the end of `config/app.php` add `'MongoLid'    => Mongolid\Laravel\Model::class` to the `$aliases` array

```php
'aliases' => [
    'App'         => Illuminate\Support\Facades\App::class,
    'Artisan'     => Illuminate\Support\Facades\Artisan::class,
    ...
    'Mongolid'    => Mongolid\Laravel\Model::class,
],
```

Lastly, be sure to configure a database connection in `config/database.php`:

Paste the settings bellow at the end of your `config/database.php`, before the last `];`:

**Notice:** It must be **outside** of `connections` array.

```php
/*
|--------------------------------------------------------------------------
| MongoDB Databases
|--------------------------------------------------------------------------
|
| MongoDB is a document database with the scalability and flexibility
| that you want with the querying and indexing that you need.
| Mongolid Laravel use this config to starting querying right now.
|
*/

'mongodb' => [
    'default' => [
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT_NUMBER', 27017),
        'database' => env('DB_DATABASE', 'my_database'),
        'username' => env('DB_USERNAME', null),
        'password' => env('DB_PASSWORD', null),
    ],
],
```

For cluster with automatic failover, you need to set `cluster` key containing all `nodes` along with `replica_set` name.

```php
'mongodb' => [
    'default' => [
        'cluster' => [
            'replica_set' => env('DB_REPLICA_SET', null),
            'nodes' => [
                'primary' => [
                    'host' => env('DB_HOST_A', 'host-a'),
                    'port' => env('DB_PORT_A', 27017),
                ],
                'secondary' => [
                    'host' => env('DB_HOST_B', 'host-b'),
                    'port' => env('DB_PORT_B', 27017),
                ],
            ],
        ],
        'database' => env('DB_DATABASE', 'mongolid'),
        'username' => env('DB_USERNAME', null),
        'password' => env('DB_PASSWORD', null),
    ],
],
```

You can configure as much nodes as needed, node names (e.g. `primary` and `secondary` ) are optional.

> **Note:** If you don't specify the `mongodb` key in your `config/database.php` MongoLid will automatically try to connect to '127.0.0.1:27017' and use a database named 'mongolid'.

You may optionally provide a `connection_string` key to set a fully-assembled connection string that will override all other connection options. More info about connection string are found in [MongoDB documentation](https://docs.mongodb.com/manual/reference/connection-string/).

```php
'mongodb' => [
    'default' => [
        'connection_string' => 'mongodb://host-a:27017,host-b:27917/mongolid?replicaSet=rs-ds123',
    ],
],
```

Also, it is possible to pass `options` and `driver_options` to MongoDB Client. Mongolid always overrides `typeMap` configuration of `driver_options` to `array` because it makes easier to use internally with models. Possible `options` and `driver_options` are present on [`MongoDB\Client` documentation](https://docs.mongodb.com/php-library/master/reference/method/MongoDBClient__construct/).

## Basic Usage

To get started, create a model. You are free to place them anywhere that can be auto-loaded according to your `composer.json` file.

### Defining a Model

```php
<?php
namespace App;

use Mongolid\Laravel\AbstractModel;

class User extends AbstractModel
{
    /**
     * @var string 
     */
    protected $collection = 'users';
}
```

In a nutshell, that's it!

## Documentation
You can access the full documentation [here](http://leroy-merlin-br.github.com/mongolid).

## Authentication

Mongolid Laravel comes with a Laravel auth provider.
In order to use it, simply change the `'driver'` provider value in your `config/auth.php` to `mongolid`
and make sure that the class specified in `model` is a MongoLid model that implements the `Authenticatable` contract:

```php
'providers' => [

    // ...

    'users' => [
        'driver' => 'mongolid',
        'model' => \App\User::class
    ],

    // ...

],
```

The `User` model should implement the `Authenticatable` interface:

```php
<?php
namespace App;

use Illuminate\Contracts\Auth\Authenticatable;
use Mongolid\Laravel\AbstractModel;

class User extends AbstractModel implements Authenticatable
{
    /**
     * @var string
     */
    protected $collection = 'users';
    
    /**
     * @var array
     */
    protected $guarded = [
        'remember_token',
        'password',
    ];

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return '_id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->_id;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }


    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}
```

Now, to log a user into your application, you may use the `auth()->attempt()` method.
You can use [any method regarding authentication](https://laravel.com/docs/5.2/authentication#included-authenticating).

## Queue Failed Job Provider

Mongolid Laravel replaces Laravel queue failed job provider to use a collection instead of a table. To configure the provider, update `failed` key on `queue.php` to include `collection` name:

```php
'failed' => [
    'database' => 'mongodb',
    'collection' => 'failed_jobs',
],
```

**Note:** `database` key is irrelevant.

## License

Mongolid & Mongolid Laravel are free software distributed under the terms of the [MIT license](http://opensource.org/licenses/MIT).
Some of the code is based on the work of Taylor Otwell and contributors on [laravel/framework](https://github.com/laravel/framework), another free software distributed under the terms of the [MIT license](http://opensource.org/licenses/MIT).

## Additional information
Made with ❤ by [Leroy Merlin Brazil](https://github.com/leroy-merlin-br) and [all contributors](https://github.com/leroy-merlin-br/mongolid-laravel/graphs/contributors).

If you have any questions, feel free to contact us.

If you any issues, please [report here](https://github.com/leroy-merlin-br/mongolid-laravel/issues).
