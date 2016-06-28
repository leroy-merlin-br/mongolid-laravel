<?php
namespace MongolidLaravel;

use MongoDB\Collection;
use MongoDB\Database;
use Mongolid\ActiveRecord;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\MessageBag;
use Mongolid\Connection\Pool;
use Mockery;

/**
 * This class extends the Mongolid\ActiveRecord, so, in order
 * to understand the ODM implementation make sure to check the
 * base class.
 *
 * The MongolidLaravel\MongolidModel simply extends the original
 * and framework agnostic model of MongoLid and implements some
 * validation rules using Laravel validation components.
 *
 * Remember, this package is meant to be used with Laravel while
 * the "leroy-merlin\mongolid" is meant to be used with other frameworks
 * or even without any.
 *
 * @license MIT
 */
abstract class MongolidModel extends ActiveRecord
{
    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = null;

    /**
     * Error message bag
     *
     * @var MessageBag
     */
    public $errors;

    /**
     * Public static mock
     *
     * @var Mockery\Mock
     */
    public static $mock;

    /**
     * Public local mock
     *
     * @var Mockery\Mock
     */
    public $localMock;

    /**
     * List of attribute names which should be hashed on save. For
     * example: array('password');
     *
     * @var array
     */
    protected $hashedAttributes = [];

    /**
     * Save the model to the database if it's valid. This method also
     * checks for the presence of the localMock in order to call the save
     * method into the existing Mock in order not to touch the database.
     *
     * @param boolean $force Force save even if the object is invalid.
     *
     * @return boolean
     */
    public function save(bool $force = false)
    {
        if ($this->localMock && $this->localMock->mockery_getExpectationsFor('save')) {
            return $this->localMock->save();
        }

        if ($force || $this->isValid()) {
            $this->hashAttributes();

            return parent::save();
        }

        return false;
    }

    /**
     * Overwrites the delete method in order to be able to check for
     * the expectation in the localMock in order to call the delete method
     * into the existing mock and avoid touching the database.
     *
     * @return boolean
     */
    public function delete()
    {
        if ($this->localMock && $this->localMock->mockery_getExpectationsFor('delete')) {
            return $this->localMock->delete();
        }

        return parent::delete();
    }

    /**
     * Verify if the model is valid
     *
     * @return boolean
     */
    public function isValid()
    {
        // Return true if there aren't validation rules
        if (! is_array(static::$rules)) {
            return true;
        }

        // Get the attributes and the rules to validate then
        $attributes = $this->attributes;
        $rules = static::$rules;

        // Verify attributes that are hashed and that have not changed
        // those doesn't need to be validated.
        foreach ($this->hashedAttributes as $hashedAttr) {
            if (isset($this->original[$hashedAttr]) && $this->$hashedAttr == $this->original[$hashedAttr]) {
                unset($rules[$hashedAttr]);
            }
        }

        // Creates validator with attributes and the rules of the object
        $validator = app(ValidationFactory::class)->make($attributes, $rules);

        // Validate and attach errors
        if ($validator->fails()) {
            $this->errors = $validator->errors();

            return false;
        }

        return true;
    }

    /**
     * Get the contents of errors attribute
     *
     * @return MessageBag Validation errors
     */
    public function errors(): MessageBag
    {
        if (! $this->errors) {
            $this->errors = new MessageBag;
        }

        return $this->errors;
    }

    /**
     * Returns the database object (the connection)
     *
     * @return Database
     */
    protected function db(): Database
    {
        $conn       = app(Pool::class)->getConnection();
        $database   = $conn->defaultDatabase;

        return $conn->getRawConnection()->$database;
    }

    /**
     * Returns the Mongo collection object
     *
     * @return Collection
     */
    protected function collection(): Collection
    {
        return $this->db()->{$this->collection};
    }

    /**
     * Hashes the attributes specified in the hashedAttributes
     * array.
     *
     * @return void
     */
    protected function hashAttributes()
    {
        foreach ($this->hashedAttributes as $attr) {
            // Hash attribute if changed
            if (! isset($this->original[$attr]) || $this->$attr != $this->original[$attr]) {
                $this->$attr = app('hash')->make($this->$attr);
            }

            // Removes any confirmation field before saving it into the database
            $confirmationField = $attr . '_confirmation';
            if ($this->$confirmationField) {
                unset($this->$confirmationField);
            }
        }
    }

    /**
     * Initiate a mock expectation on the facade.
     *
     * @param  string $name      Name of the method being called.
     * @param  array  $arguments Method arguments.
     *
     * @return \Mockery\Expectation
     */
    public static function __callStatic($name, $arguments)
    {
        if ($name == 'shouldReceive') {
            if (! static::$mock) {
                static::$mock = Mockery::mock(get_called_class() . 'Mock');
            }

            return call_user_func_array([static::$mock, 'shouldReceive'], $arguments);
        }
    }

    /**
     * Initiate a mock expectation that is specific for the save method.
     *
     * @return \Mockery\Expectation
     */
    public function shouldReceiveSave()
    {
        return $this->localMockShouldReceive('save');
    }

    /**
     * Initiate a mock expectation that is specific for the delete method.
     *
     * @return \Mockery\Expectation
     */
    public function shouldReceiveDelete()
    {
        return $this->localMockShouldReceive('delete');
    }

    /**
     * Initiate a mock expectation that is specific for the given method.
     *
     * @param string $method Name of the method being mocked.
     *
     * @return \Mockery\Expectation
     */
    protected function localMockShouldReceive(string $method)
    {
        if (! $this->localMock) {
            $this->localMock = Mockery::mock(get_called_class() . 'Mock');
        }

        return call_user_func_array([$this->localMock, 'shouldReceive'], [$method]);
    }

    /**
     * Gets the first entity of this kind that matches the query
     *
     * @param  mixed $query MongoDB selection criteria.
     *
     * @return ActiveRecord
     */
    public static function first($query = [])
    {
        return static::callMockOrParent('first', func_get_args());
    }

    /**
     * Gets a cursor of this kind of entities that matches the query from the
     * database
     *
     * @param  array $query MongoDB selection criteria.
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public static function where(array $query = [])
    {
        return static::callMockOrParent('where', func_get_args());
    }

    /**
     * Gets a cursor of this kind of entities from the database
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public static function all()
    {
        return static::callMockOrParent('all', func_get_args());
    }

    /**
     * Calls mock method if its have expectations. Calls parent method otherwise
     *
     * @param string $method    Name of the method being called.
     * @param array  $arguments Arguments to pass in method call.
     *
     * @return  mixed See parent implementation
     */
    protected static function callMockOrParent(string $method, array $arguments)
    {
        $classToCall = 'parent';

        if (static::$mock && static::$mock->mockery_getExpectationsFor($method)) {
            $classToCall = static::$mock;
        }

        return call_user_func_array([$classToCall, $method], $arguments);
    }
}
