[![Latest Stable Version](https://poser.pugx.org/zizaco/mongolid-laravel/v/stable.png)](https://packagist.org/packages/zizaco/mongolid-laravel)
[![Monthly Downloads](https://poser.pugx.org/zizaco/mongolid-laravel/d/monthly.png)](https://packagist.org/packages/zizaco/mongolid-laravel)
[![Latest Unstable Version](https://poser.pugx.org/zizaco/mongolid-laravel/v/unstable.png)](https://packagist.org/packages/zizaco/mongolid-laravel)
[![License](https://poser.pugx.org/zizaco/mongolid-laravel/license.png)](https://packagist.org/packages/zizaco/mongolid-laravel)

![MongoLid](https://dl.dropboxusercontent.com/u/12506137/libs_bundles/mongolid_banner.png)

# MongoLid (Laravel4 Package)

- [Introduction](#introduction)
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Odm Cursor](#odm-cursor)
- [Mass Assignment](#mass-assignment)
- [Insert, Update, Delete](#insert-update-delete)
- [Soft Deleting](#soft-deleting)
- [Timestamps](#timestamps)
- [Query Scopes](#query-scopes)
- [Relationships](#relationships)
- [Converting To Arrays / JSON](#converting-to-arrays-or-json)
- [Authentication](#authentication)
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Additional Information](#additional_information)

<a name="introduction"></a>
## Introduction

MongoLid ODM (Object Document Mapper) provides a beautiful, simple implementation for working with MongoDB. Each database collection can have a corresponding "Model" which is used to interact with that collection.

> Note: The ODM implementation is within the [(non laravel) mongolid repository](https://github.com/Zizaco/mongolid).

<a name="installation"></a>
## Installation

In the `require` key of `composer.json` file add the following

```yml
    "zizaco/mongolid": "dev-master",
    "zizaco/mongolid-laravel": "dev-master"
```

Run the Composer update comand

    $ composer update

In your `config/app.php` add `'Zizaco\MongolidLaravel\MongolidServiceProvider'` to the end of the `$providers` array

```php
    'providers' => array(

        'Illuminate\Foundation\Providers\ArtisanServiceProvider',
        'Illuminate\Auth\AuthServiceProvider',
        ...
        'Zizaco\MongolidLaravel\MongolidServiceProvider',

    ),
```

At the end of `config/app.php` add `'MongoLid'    => 'Zizaco\MongolidLaravel\MongoLid'` to the `$aliases` array

```php
    'aliases' => array(

        'App'        => 'Illuminate\Support\Facades\App',
        'Artisan'    => 'Illuminate\Support\Facades\Artisan',
        ...
        'MongoLid'    => 'Zizaco\MongolidLaravel\MongoLid',

    ),
```

And least, be sure to configure a database connection in `app/config/database.php`:

Paste the settings bellow at the end of your `database.php`, before the last `);`:

```php
    /*
    |--------------------------------------------------------------------------
    | MongoDB Databases
    |--------------------------------------------------------------------------
    |
    */

    'mongodb' => array(

        'default' => array(
            'host'     => '127.0.0.1',
            'port'     => 27017,
            'database' => 'my_database',
            'username'     => '',
            'password'     => '',
        ),
    ),
```

> **Note:** If you don't specify the key above in your `config/database.php`. The MongoLid will automatically try to connect to 127.0.0.1:27017 and use a database named 'mongolid'.

You may optionally provide a 'connectionString' key to set a fully-assembled connection string (useful for configuring fun things like read preference, replica sets, etc.) this will override all other connection options.

<a name="basic-usage"></a>
## Basic Usage

To get started, create an MongoLid model. Models typically live in the `app/models` directory, but you are free to place them anywhere that can be auto-loaded according to your `composer.json` file.

**Defining An MongoLid Model**

```php
    class User extends MongoLid {}
```

Note that we did not tell MongoLid which collection to use for our `User` model. So, in this case, MongoLid **will not save the model into the database**. This can be used for models that represents objects that will be embedded within another object and will not have their own collection.

You may specify a collection by defining a `collection` property on your model:

```php
    class User extends MongoLid {

        protected $collection = 'users';

    }
```

MongoLid will also assume each collection has a primary key attribute named `_id`. Since MongoDB requires an `_id` for every single document. The `_id` attribute can be of any kind. The default kind for this attribute is `MongoId`. [Learn more about the MongoId](http://php.net/manual/en/class.mongoid.php).

> **Note:** MongoLid will automatically convert strings in MongoId format (For example: "4af9f23d8ead0e1d32000000") to MongoId when querying or saving an object.

Once a model is defined, you are ready to start retrieving and creating documents in your collection.

**Retrieving All Models**

```php
    $users = User::all();
```

**Retrieving A Document By Primary Key**

```php
    $user = User::first('4af9f23d8ead0e1d32000000');

    // or

    $user = User::find('4af9f23d8ead0e1d32000000');
```

**Retrieving One Document By attribute**

```php
    $user = User::first(['name'=>'bob']);
```

**Retrieving One or Many Document By attribute**

```php
    $users = User::find(['status'=>'new']);

    if( $users instanceOf User ) // Check if One user has been retrieved
    {
        echo $users->name;
    }
    else // Means that many users has been found matching the criterea "status: new"
    {
        foreach( $users as $user )
        {
            echo $user->name;
        }
    }
```

**Retrieving Many Documents By attribute**

```php
    $users = User::where(['role'=>'visitor']);
```

**Querying Using MongoLid Models**

```php
    $users = User::where(['votes'=>['$gt'=>100]])->limit(10);

    foreach ($users as $user)
    {
        var_dump($user->name);
    }
```

**MongoLid Count**

```php
    $count = User::where(['votes'=>['$gt'=>100]])->count();
```

<a name="odm-cursor"></a>
## Odm Cursor

In MongoDB, a cursor is used to iterate through the results of a database query. For example, to query the database and see all results:

```php
    $cursor = User::where(['kind'=>'visitor']);
```

In the above example, the $cursor variable will be a `Zizaco\Mongolid\OdmCursor`.

The MongoLid's OdmCursor extends the original MongoCursor object of the official MongoDB Driver. [Learn more about MongoCursor](http://php.net/manual/en/class.mongocursor.php). The main difference between the original `MongoCursor` and the MongoLid's `OdmCursor` is that the `OdmCursor` returns objects (instances of your models) instead of arrays.

The cursor object has alot of methods that helps you to iterate, refine and get information. For example:

```php
    $cursor = User::where(['kind'=>'visitor']);

    // Return an explanation of the query, often useful for optimization and debugging
    $cursor->explain();

    // Sorts the results by given fields. In the example bellow, it sorts by username DESC
    $cursor->sort( ['username'=>-1] );

    // Limits the number of results returned. Good pagination
    $cursor->limit( 10 );

    // Skips a number of results. Good for pagination
    $cursor->skip( 20 );

    // Checks if the cursor is reading a valid result.
    $cursor->valid();

    // Returns the first result
    $cursor->first();
```

You can also chain some methods:

```php
    $page = 2;

    // In order to display 10 results per page
    $cursor = User::all()->sort( ['_id'=>1] )->skip( 10 * $page )->limit( 10 );
```

[Learn more about MongoCursor](http://php.net/manual/en/class.mongocursor.php)

<a name="mass-assignment"></a>
## Mass Assignment

When creating a new model, you pass an array of attributes to the model constructor. These attributes are then assigned to the model via mass-assignment. This is convenient; however, can be a **serious** security concern when blindly passing user input into a model. If user input is blindly passed into a model, the user is free to modify **any** and **all** of the model's attributes. By default, all attributes are fillable.

To get started, set the `fillable` or `guarded` properties on your model.

The `fillable` property specifies which attributes should be mass-assignable. This can be set at the class or instance level.

**Defining Fillable Attributes On A Model**

```php
    class User extends MongoLid {

        protected $fillable = array('first_name', 'last_name', 'email');

    }
```

In this example, only the three listed attributes will be mass-assignable.

The inverse of `fillable` is `guarded`, and serves as a "black-list" instead of a "white-list":

**Defining Guarded Attributes On A Model**

```php
    class User extends MongoLid {

        protected $guarded = array('id', 'password');

    }
```

In the example above, the `id` and `password` attributes may **not** be mass assigned. All other attributes will be mass assignable.

<a name="insert-update-delete"></a>
## Insert, Update, Delete

To create a new document in the database from a model, simply create a new model instance and call the `save` method.

**Saving A New Model**

```php
    $user = new User;

    $user->name = 'John';

    $user->save();
```

> **Note:** Typically, your MongoLid models will have auto-generated `_id` keys. However, if you wish to specify your own keys, set the `_id` attribute.

To update a model, you may retrieve it, change an attribute, and use the `save` method:

**Updating A Retrieved Model**

```php
    $user = User::first('4af9f23d8ead0e1d32000000');

    $user->email = 'john@foo.com';

    $user->save();
```

To delete a model, simply call the `delete` method on the instance:

**Deleting An Existing Model**

```php
    $user = User::first('4af9f23d8ead0e1d32000000');

    $user->delete();
```

<a name="relationships"></a>
## Relationships

Of course, your database collections are probably related to one another. For example, a blog post may have many comments, or an order could be related to the user who placed it. MongoLid makes managing and working with these relationships easy. MongoDB and MongoLid in short supports four types of relationships:

- [Embeds One](#embeds-one)
- [Embeds Many](#embeds-many)
- [References One](#references-one)
- [References Many](#references-many)

> **Note:** MongoDB **relationships doesn't works like in a Relational database**. In MongoDB, data modeling decisions involve determining how to structure the documents to model the data effectively. The primary decision is whether to embed or to use references. See [MongoDB - Data Modeling Decisions](http://docs.mongodb.org/manual/core/data-modeling/#data-modeling-decisions) for more information on this subject.

<a name="embeds-one"></a>
### Embeds One

Read [MongoDB - Data Modeling Embedding](http://docs.mongodb.org/manual/core/data-modeling/#embedding) to learn more how to take advantage of document embedding.

A Embeds One relationship is a very basic relation. For example, a `User` model might have one `Phone`. We can define this relation in Mongolid:

**Defining A Embeds One Relation**

```php
    // models/User.php
    class User extends Mongolid {

        // This model is saved in the collection users
        protected $collection = 'users';

        // Method that will be used to access the phone
        public function phone()
        {
            return $this->embedsOne('Phone', 'phone');
        }

    }

    // models/Phone.php
    class Phone extends Mongolid {

        // This model will be embedded only
        protected $collection = null;

        public function getFullPhone()
        {
            return '+' . $this->regionCode . $this->number;
        }

    }
```

The first argument passed to the `embedsOne` method is the name of the related model. The second argument is in what attribute that object (array with it's attributes) is saved. Once the relationship is defined, we may retrieve it using:

```php
    $phone = User::find('4af9f23d8ead0e1d32000000')->phone();
```

This statement will perform the following:

- Query for the user with the `_id` _'4af9f23d8ead0e1d32000000'_
- Instantiate a **Phone** object with the attributes found in _'phone'_ attribute of the user
- Return that object

In order to embed a document to be used in a Embeds One relationship, simply set the attribute to an array with the attributes of the embeded model. For example:

```php
    // The object that will be embeded
    $phoneObj = new Phone;
    $phoneObj->regionCode = '55';
    $phoneObj->number = '1532323232';

    // The object that will contain the phone
    $user = User::first('4af9f23d8ead0e1d32000000');

    // This method will embed the $phoneObj into the phone attribute of the user
    $user->embed( 'phone', $phoneObj );

    // This is an alias to the method called above.
    $user->embedToPhone( $phoneObj );

    // This will will also work
    $user->phone = $phoneObj->attributes;

    // Or
    $user->phone = $phoneObj->toArray();

    // Or even
    $user->phone = array(
        'regionCode' => $phoneObj->regionCode,
        'number' => $phoneObj->number
    );

    $user->save();

    // Now we can retrieve the object by calling
    $user->phone(); // Will return a Phone object similar to $phoneObj
```

> **Note:** When using MongoLid models you will need to call the `save()` method after embeding or attaching objects. The changes will only persists after you call the 'save()' method.

<a name="embeds-many"></a>
### Embeds many

An example of a Embeds Many relation is a blog post that "has many" comments. We can model this relation like so:

```php
    // models/Post.php
    class Post extends Mongolid {

        protected $collection = 'posts';

        public function comments()
        {
            return $this->embedsMany('Comment', 'comments');
        }

    }

    // models/Comment.php
    class Comment extends Mongolid{

        // This model will be embedded only
        protected $collection = null;

    }
```

Now we can access the post's comments **array** through the comments method:

```php
    $comments = Post::find('4af9f23d8ead0e1d32000000')->comments();
```

Now you can iterate the array of Comment objects

```php
    foreach( $comments as $comment )
    {
        if(! $comment->hidden )
            echo $comment;
    }
```

In order to embed a document to be used in a Embeds One relationship, you may use the `embed` method or the alias `embedToAttribute`:

```php
    $commentA = new Comment;
    $commentA->content = 'Cool feature bro!';

    $commentB = new Comment;
    $commentB->content = 'Awesome!';

    $post = Post::first('4af9f23d8ead0e1d32000000');

    // Both ways work
    $post->embedToComments( $commentA );
    $post->embed( 'Comments', $commentB );

    $post->save();
```

> **Note:** When using MongoLid models you will need to call the `save()` method after embeding or attaching objects. The changes will only persists after you call the 'save()' method.

<a name="references-one"></a>
### References One

In MongoLid a reference is made by storing the `_id` of the referenced object. 

Referencing provides more flexibility than embedding; however, to resolve the references, client-side applications must issue follow-up queries. In other words, using references requires more roundtrips to the server.

In general, use references when embedding would result in duplication of data and would not provide sufficient read performance advantages to outweigh the implications of the duplication. Read [MongoDB - Data Modeling Referencing](http://docs.mongodb.org/manual/core/data-modeling/#referencing) to learn more how to take advantage of referencing in MongoDB.

**Defining A References One Relation**

```php
    // models/Post.php
    class Post extends Mongolid {

        protected $collection = 'posts';

        public function author()
        {
            return $this->referencesOne('User', 'author');
        }

    }

    // models/User.php
    class User extends Mongolid{

        protected $collection = 'users';

    }
```

The first argument passed to the `referencesOne` method is the name of the related model, the second argument is the attribute where the referenced model `_id` will be stored. Once the relationship is defined, we may retrieve it using the following method:

```php
    $user = Post::find('4af9f23d8ead0e1d32000000')->author();
```

This statement will perform the following:

- Query for the post with the `_id` _'4af9f23d8ead0e1d32000000'_
- Query for the user with the `_id` equals to the _author_ attribute of the post
- Return that object

In order to set a reference to a document, simply set the attribute used in the relationship to the reference's `_id` or use the attach method or it's alias. For example:

```php
    // The object that will be embeded
    $userObj = new User;
    $userObj->name = 'John';
    $userObj->save() // This will populates the $userObj->_id

    // The object that will contain the user
    $post = Post::first('4af9f23d8ead0e1d32000000');

    // This method will attach the $phoneObj into the phone attribute of the user
    $post->attach( 'author', $userObj );

    // This is an alias to the method called above.
    $post->attachToAuthor( $userObj );

    // This will will also work
    $post->author = $userObj->_id;

    $post->save();

    $post->author(); // Will return a User object
```

> **Note:** When using MongoLid models you will need to call the `save()` method after embeding or attaching objects. The changes will only persists after you call the 'save()' method.

<a name="references-many"></a>
### References Many

In MongoLid a references many is made by storing an array containing the `_id` of the referenced objects. 

Referencing provides more flexibility than embedding; however, to resolve the references, client-side applications must issue follow-up queries. In other words, using references requires more roundtrips to the server.

In general, use references when embedding would result in duplication of data and would not provide sufficient read performance advantages to outweigh the implications of the duplication. Read [MongoDB - Data Modeling Referencing](http://docs.mongodb.org/manual/core/data-modeling/#referencing) to learn more how to take advantage of referencing in MongoDB.

**Defining A References Many Relation**

```php
    // models/User.php
    class User extends Mongolid{

        protected $collection = 'users';

        public function posts()
        {
            return $this->referencesMany('Post', 'posts');
        }

    }

    // models/Post.php
    class Post extends Mongolid {

        protected $collection = 'posts';

    }
```

The first argument passed to the `referencesMany` method is the name of the related model, the second argument is the attribute where the `_id`s will be stored. Once the relationship is defined, we may retrieve it using the following method:

```php
    $posts = User::find('4af9f23d8ead0e1d32000000')->posts();
```

This statement will perform the following:

- Query for the user with the `_id` _'4af9f23d8ead0e1d32000000'_
- Query for all the posts with the `_id` in the user's _posts_ attribute
- Return the [OdmCursor](#odm-cursor) with the related posts

In order to set a reference to a document use the attach method or it's alias. For example:

```php
    $postA = new Post;
    $postA->title = 'Nice post';

    $postB = new Post;
    $postB->title = 'Nicer post';

    $user = User::first('4af9f23d8ead0e1d32000000');

    // Both ways work
    $user->attachToPosts( $postA );
    $user->attach( 'posts', $postB );

    $user->save();
```

> **Note:** When using MongoLid models you will need to call the `save()` method after embeding or attaching objects. The changes will only persists after you call the 'save()' method.

<a name="converting-to-arrays-or-json"></a>
## Converting To Arrays / JSON

When building JSON APIs, you may often need to convert your models to arrays or JSON. So, MongoLid includes methods for doing so. To convert a model and its loaded relationship to an array, you may use the `toArray` method:

**Converting A Model To An Array**

```php
    $user = User::with('roles')->first();

    return $user->toArray();
```

Note that entire collections of models may also be converted to arrays:

```php
    return User::all()->toArray();
```

To convert a model to JSON, you may use the `toJson` method:

**Converting A Model To JSON**

```php
    return User::find(1)->toJson();
```

Note that when a model or collection is cast to a string, it will be converted to JSON, meaning you can return MongoLid objects directly from your application's routes!

**Returning A Model From A Route**

```php
    Route::get('users', function()
    {
        return User::all();
    });
```

<a name="authentication"></a>
## Authentication

MongoLid Laravel comes with a Laravel auth driver. In order to use it, simply change the `'driver'` value in your `app/config/auth.php` to 'mongoLid' and make sure that the class specified in `'model'` is a MongoLid model that implements the UserInterface:

```php
    ...

    'driver' => 'mongoLid',

    ...

    'model' => 'User',

    ...
```

The `User` model should implement the `UserInterface`:

    <?php

    use Illuminate\Auth\UserInterface;

    class User extends MongoLid implements UserInterface {

        /**
         * The database collection used by the model.
         *
         * @var string
         */
        protected $collection = 'users';

        /**
         * The attributes excluded from the model's JSON form.
         *
         * @var array
         */
        protected $hidden = array('password');

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

    }

Now, to log a user into your application, you may use the `Auth::attempt` method. You can use [any method regarding authentication](http://four.laravel.com/docs/security#authenticating-users).

<a name="troubleshooting"></a>
## Troubleshooting

**"PHP Fatal error: Class 'MongoClient' not found in ..."**

The `MongoClient` class is contained in the [MongoDB driver](http://pecl.php.net/package/mongo) for PHP. [Here is an installation guide](http://www.php.net/manual/en/mongo.installation.php). The driver is a PHP extension written in C and maintained by [MongoDB](https://mongodb.com). MongoLid and most other MongoDB PHP libraries utilize it in order to be fast and reliable.

**"Class 'MongoClient' not found in ..." in CLI persists even with MongoDB driver installed.**

Make sure that the **php.ini** file used in the CLI environment includes the MongoDB extension. In some systems, the default PHP installation uses different **.ini** files for the web and CLI environments.

Run `php -i | grep 'Configuration File'` in a terminal to check the **.ini** that is being used.

To check if PHP in the CLI environment is importing the driver properly run `php -i | grep 'Mongo'` in your terminal. You should get output similar to:

```
$ php -i | grep 'Mongo'
MongoDB Support => enabled
```

<a name="license"></a>
## License

MongoLid & MongoLid Laravel are free software distributed under the terms of the [MIT license](http://opensource.org/licenses/MIT)

<a name="additional_information"></a>
## Aditional information

Any questions, feel free to contact me.

Any issues, please [report here](https://github.com/Zizaco/mongolid-laravel/issues)
