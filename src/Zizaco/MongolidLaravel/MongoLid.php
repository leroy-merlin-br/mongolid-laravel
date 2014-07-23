<?php

namespace Zizaco\MongolidLaravel;

/**
 * This class extends the Zizaco\Mongolid\Model, so, in order
 * to understand the ODM implementation make sure to check the
 * base class.
 *
 * The Zizaco\MongolidLaravel\MongoLid simply extends the original
 * and framework agnostic model of MongoLid and implements some
 * validation rules using Laravel validation components.
 *
 * Remember, this package is meant to be used with Laravel while
 * the "zizaco\mongolid" is meant to be used with other frameworks
 * or even without any.
 *
 * @license MIT
 * @author  Zizaco Zizuini <zizaco@gmail.com>
 */
abstract class MongoLid extends \Zizaco\Mongolid\Model implements \ArrayAccess
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
     * @var Illuminate\Support\MessageBag
     */
    public $errors;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected static $dispatcher;

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
    protected $hashedAttributes = array();

    /**
     * Sets the database and the cache component of the model
     * If you extend the __construct() method, please don't forget
     * to call parent::__construct()
     */
    public function __construct()
    {
        if (is_null($this->database)) {
            $this->database = \Config::get('database.mongodb.default.database', null);
        }

        static::$cacheComponent = \App::make('cache');
    }

    /**
     * Save the model to the database if it's valid. This method also
     * checks for the presence of the localMock in order to call the save
     * method into the existing Mock in order not to touch the database.
     *
     * @param $force Force save even if the object is invalid
     * @return bool
     */
    public function save($force = false)
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
     * @return bool
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
     * @return bool
     */
    public function isValid()
    {
        /**
         * Return true if there arent validation rules
         */
        if (! is_array(static::$rules)) {
            return true;
        }

        /**
         * Get the attributes and the rules to validate then
         */
        $attributes = $this->attributes;
        $rules = static::$rules;

        /**
         * Verify attributes that are hashed and that have not changed
         * those doesn't need to be validated.
         */
        foreach ($this->hashedAttributes as $hashedAttr) {
            if (isset($this->original[$hashedAttr]) && $this->$hashedAttr == $this->original[$hashedAttr]) {
                unset($rules[$hashedAttr]);
            }
        }

        /**
         * Creates validator with attributes and the rules of the object
         */
        $validator = \Validator::make($attributes, $rules);

        /**
         * Validate and attach errors
         */
        if ($validator->fails()) {
            $this->errors = $validator->errors();
            return false;
        }

        return true;
    }

    /**
     * Get the contents of errors attribute
     *
     * @return Illuminate\Support\MessageBag Validation errors
     */
    public function errors()
    {
        if (! $this->errors) {
            $this->errors = new \Illuminate\Support\MessageBag;
        }

        return $this->errors;
    }

    /**
     * Returns a new instance of the current model
     * Overwrites the original newInstance method in order
     * to use the IoC container.
     *
     * @return  mixed An instance of the current model
     */
    public static function newInstance()
    {
        return app()->make(get_called_class());
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
            /**
             * Hash attribute if changed
             */
            if (! isset($this->original[$attr]) || $this->$attr != $this->original[$attr]) {
                $this->$attr = static::$app['hash']->make($this->$attr);
            }

            /**
             * Removes any confirmation field before saving it into the database
             */
            $confirmationField = $attr.'_confirmation';
            if ($this->$confirmationField) {
                unset($this->$confirmationField);
            }
        }
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Events\Dispatcher  $dispatcher
     * @return void
     */
    public static function setEventDispatcher(\Illuminate\Events\Dispatcher $dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * Fire the given event for the model.
     *
     * @param  string $event
     * @param  bool   $halt
     * @return mixed
     */
    protected function fireModelEvent($event, $halt = true)
    {
        if (! isset(static::$dispatcher)) {
            return true;
        }

        // We will append the names of the class to the event to distinguish it from
        // other model events that are fired, allowing us to listen on each model
        // event set individually instead of catching event for all the models.
        $event = "mongolid.{$event}: ".get_class($this);

        $method = $halt ? 'until' : 'fire';

        return static::$dispatcher->$method($event, $this);
    }

    /**
     * Initiate a mock expectation on the facade.
     *
     * @param  dynamic
     * @return \Mockery\Expectation
     */
    public static function __callStatic($name, $arguments)
    {
        if ($name == 'shouldReceive') {
            if (! static::$mock) {
                static::$mock = \Mockery::mock(get_called_class().'Mock');
            }

            return call_user_func_array(array(static::$mock, 'shouldReceive'), $arguments);
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
     * @return \Mockery\Expectation
     */
    protected function localMockShouldReceive($method)
    {
        if (! $this->localMock) {
            $this->localMock = \Mockery::mock(get_called_class().'Mock');
        }

        return call_user_func_array(array($this->localMock, 'shouldReceive'), [$method]);
    }

    /**
     * Overwrites the "static" method in order to make it mockable
     *
     */
    public static function first($id = array(), $fields = array())
    {
        return static::callMockOrParent('first', func_get_args());
    }

    /**
     * Overwrites the "static" method in order to make it mockable
     *
     */
    public static function find($id = array(), $fields = array(), $cachable = false)
    {
        return static::callMockOrParent('find', func_get_args());
    }

    /**
     * Overwrites the "static" method in order to make it mockable
     *
     */
    public static function where($query = array(), $fields = array(), $cachable = false)
    {
        return static::callMockOrParent('where', func_get_args());
    }

    /**
     * Overwrites the "static" method in order to make it mockable
     *
     */
    public static function all($fields = array())
    {
        return static::callMockOrParent('all', func_get_args());
    }

    /*
     * Check whether an offset exists
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /*
     * Get the value of an offset
     */
    public function offsetGet($offset)
    {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    /*
     * Set the value of an offset
     */
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    /*
     * Delete the value of an offset
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Calls mock method if its have expectations. Calls parent method otherwise
     *
     * @param string $method
     * @param array  $arguments Arguments to pass in method call
     *
     * @return  mixed See parent implementation
     */
    protected static function callMockOrParent($method, array $arguments)
    {
        $classToCall = 'parent';

        if (static::$mock && static::$mock->mockery_getExpectationsFor($method)) {
            $classToCall = static::$mock;
        }

        return call_user_func_array(array($classToCall, $method), $arguments);
    }
}
