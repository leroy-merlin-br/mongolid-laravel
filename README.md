![MongoLid](https://dl.dropboxusercontent.com/u/12506137/libs_bundles/mongolid_banner.png)

# MongoLid (Laravel4 Package)

- [Introduction](#introduction)
- [Basic Usage](#basic-usage)
- [Mass Assignment](#mass-assignment)
- [Insert, Update, Delete](#insert-update-delete)
- [Soft Deleting](#soft-deleting)
- [Timestamps](#timestamps)
- [Query Scopes](#query-scopes)
- [Relationships](#relationships)
- [Querying Relations](#querying-relations)
- [Eager Loading](#eager-loading)
- [Inserting Related Models](#inserting-related-models)
- [Touching Parent Timestamps](#touching-parent-timestamps)
- [Working With Pivot collections](#working-with-pivot-collections)
- [Collections](#collections)
- [Accessors & Mutators](#accessors-and-mutators)
- [Model Events](#model-events)
- [Converting To Arrays / JSON](#converting-to-arrays-or-json)

<a name="introduction"></a>
## Introduction

MongoLid ODM (**O**bject **D**ocument **M**apper) provides a beautiful, simple implementation for working with MongoDB. Each database collection can have a corresponding "Model" which is used to interact with that collection.

Before getting started, be sure to configure a database connection in `app/config/database.php`:

Paste the settings bellow at the end of your `database.php`, before the last `);`:

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
        ),
    ),

<a name="basic-usage"></a>
## Basic Usage

To get started, create an MongoLid model. Models typically live in the `app/models` directory, but you are free to place them anywhere that can be auto-loaded according to your `composer.json` file.

**Defining An MongoLid Model**

    class User extends MongoLid {}

Note that we did not tell MongoLid which collection to use for our `User` model. So, in this case, MongoLid **will not save the model into the database**. This can be used for models that represents objects that will be embedded within another object and will not have their own collection.

You may specify a collection by defining a `collection` property on your model:

    class User extends MongoLid {

        protected $collection = 'users';

    }

MongoLid will also assume each collection has a primary key attribute named `_id`. Since MongoDB requires an `_id` for every single document. The `_id` attribute can be of any kind. The default kind for this attribute is `MongoId`. [Learn more about the MongoId](http://php.net/manual/en/class.mongoid.php).

> **Note:** MongoLid will automatically convert strings in MongoId format (For example: "4af9f23d8ead0e1d32000000") to MongoId when querying or saving an object.

Once a model is defined, you are ready to start retrieving and creating documents in your collection.

**Retrieving All Models**

    $users = User::all();

**Retrieving A Document By Primary Key**

    $user = User::first('4af9f23d8ead0e1d32000000');

    // or

    $user = User::find('4af9f23d8ead0e1d32000000');

**Retrieving One Document By attribute**

    $user = User::first(['name'=>'bob']);

**Retrieving One or Many Document By attribute**

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

**Retrieving Many Documents By attribute**

    $users = User::where(['role'=>'visitor']);

**Querying Using MongoLid Models**

    $users = User::where(['votes'=>'$gt'=>[100]])->limit(10);

    foreach ($users as $user)
    {
        var_dump($user->name);
    }

**MongoLid Count**

    $count = User::where(['votes'=>'$gt'=>[100]])->count();
s
<a name="mass-assignment"></a>
## Mass Assignment

When creating a new model, you pass an array of attributes to the model constructor. These attributes are then assigned to the model via mass-assignment. This is convenient; however, can be a **serious** security concern when blindly passing user input into a model. If user input is blindly passed into a model, the user is free to modify **any** and **all** of the model's attributes. By default, all attributes are fillable.

To get started, set the `fillable` or `guarded` properties on your model.

The `fillable` property specifies which attributes should be mass-assignable. This can be set at the class or instance level.

**Defining Fillable Attributes On A Model**

    class User extends MongoLid {

        protected $fillable = array('first_name', 'last_name', 'email');

    }

In this example, only the three listed attributes will be mass-assignable.

The inverse of `fillable` is `guarded`, and serves as a "black-list" instead of a "white-list":

**Defining Guarded Attributes On A Model**

    class User extends MongoLid {

        protected $guarded = array('id', 'password');

    }

In the example above, the `id` and `password` attributes may **not** be mass assigned. All other attributes will be mass assignable.

<a name="insert-update-delete"></a>
## Insert, Update, Delete

To create a new document in the database from a model, simply create a new model instance and call the `save` method.

**Saving A New Model**

    $user = new User;

    $user->name = 'John';

    $user->save();

> **Note:** Typically, your MongoLid models will have auto-generated `_id` keys. However, if you wish to specify your own keys, set the `_id` attribute.

To update a model, you may retrieve it, change an attribute, and use the `save` method:

**Updating A Retrieved Model**

    $user = User::first('4af9f23d8ead0e1d32000000');

    $user->email = 'john@foo.com';

    $user->save();

To delete a model, simply call the `delete` method on the instance:

**Deleting An Existing Model**

    $user = User::first('4af9f23d8ead0e1d32000000');

    $user->delete();

<a name="relationships"></a>
## Relationships

Of course, your database collections are probably related to one another. For example, a blog post may have many comments, or an order could be related to the user who placed it. MongoLid makes managing and working with these relationships easy. MongoDB and MongoLid in short supports four types of relationships:

- [References One](#references-one)
- [References Many](#references-many)
- [Embeds One](#embeds-one)
- [Embeds Many](#embeds-many)

<a name="dynamic-properties"></a>
### Dynamic Properties

MongoLid allows you to access your relations via dynamic properties. MongoLid will automatically load the relationship for you, and is even smart enough to know whether to call the `get` (for one-to-many relationships) or `first` (for one-to-one relationships) method.  It will then be accessible via a dynamic property by the same name as the relation. For example, with the following model `$phone`:

    class Phone extends MongoLid {

        public function user()
        {
            return $this->referencesOne('User');
        }

    }

    $phone = Phone::find(1);
    
Instead of echoing the user's email like this:

    echo $phone->user()->first()->email;

It may be shortened to simply:

    echo $phone->user->email;

<a name="converting-to-arrays-or-json"></a>
## Converting To Arrays / JSON

When building JSON APIs, you may often need to convert your models and relationships to arrays or JSON. So, MongoLid includes methods for doing so. To convert a model and its loaded relationship to an array, you may use the `toArray` method:

**Converting A Model To An Array**

    $user = User::with('roles')->first();

    return $user->toArray();

Note that entire collections of models may also be converted to arrays:

    return User::all()->toArray();

To convert a model to JSON, you may use the `toJson` method:

**Converting A Model To JSON**

    return User::find(1)->toJson();

Note that when a model or collection is cast to a string, it will be converted to JSON, meaning you can return MongoLid objects directly from your application's routes!

**Returning A Model From A Route**

    Route::get('users', function()
    {
        return User::all();
    });
