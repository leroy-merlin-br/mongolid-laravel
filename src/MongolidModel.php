<?php

namespace MongolidLaravel;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\MessageBag;
use Mockery;
use Mockery\Expectation;
use MongoDB\Collection;
use MongoDB\Database;
use Mongolid\ActiveRecord;
use Mongolid\Connection\Pool;

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
     * Validation rules.
     *
     * @var array
     */
    public $rules = null;

    /**
     * Error message bag.
     *
     * @var MessageBag
     */
    public $errors;

    /**
     * Public static mock.
     *
     * @var Mockery\Mock
     */
    public static $mock;

    /**
     * Public local mock.
     *
     * @var Mockery\Mock
     */
    public $localMock;

    /**
     * List of attribute names which should be hashed on save. For
     * example: array('password');.
     *
     * @var array
     */
    protected $hashedAttributes = [];

    /**
     * Save the model to the database if it's valid. This method also
     * checks for the presence of the localMock in order to call the save
     * method into the existing Mock in order not to touch the database.
     *
     * @param bool $force force save even if the object is invalid
     *
     * @return bool
     */
    public function save(bool $force = false)
    {
        if ($this->localMockHasExpectationsFor('save')) {
            return $this->getLocalMock()->save();
        }

        if ($force || $this->isValid()) {
            $this->hashAttributes();

            return parent::save();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $this->hashAttributes();

        return parent::update();
    }

    /**
     * Overwrites the delete method in order to be able to check for
     * the expectation in the localMock in order to call the delete method
     * into the existing mock and avoid touching the database.
     *
     * @return bool
     */
    public function delete()
    {
        if ($this->localMockHasExpectationsFor('delete')) {
            return $this->getLocalMock()->delete();
        }

        return parent::delete();
    }

    /**
     * Verify if the model is valid by running its validation rules,
     * defined on attribute `$rules`.
     *
     * @return bool
     */
    public function isValid()
    {
        // Return true if there aren't validation rules
        if (!is_array($this->rules)) {
            return true;
        }

        // Get the attributes and the rules to validate then
        $attributes = $this->attributes;
        $rules = $this->rules;

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
        if ($hasErrors = $validator->fails()) {
            $this->errors = $validator->errors();
        }

        return !$hasErrors;
    }

    /**
     * Get the contents of errors attribute.
     *
     * @return MessageBag Validation errors
     */
    public function errors(): MessageBag
    {
        if (!$this->errors) {
            $this->errors = new MessageBag();
        }

        return $this->errors;
    }

    /**
     * Returns the database object (the connection).
     *
     * @return Database
     */
    protected function db(): Database
    {
        $conn = app(Pool::class)->getConnection();
        $database = $conn->defaultDatabase;

        return $conn->getRawConnection()->$database;
    }

    /**
     * Returns the Mongo collection object.
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
     */
    protected function hashAttributes()
    {
        foreach ($this->hashedAttributes as $attr) {
            // Hash attribute if changed
            if (!isset($this->original[$attr]) || $this->$attr != $this->original[$attr]) {
                $this->$attr = app(Hasher::class)->make($this->$attr);
            }

            // Removes any confirmation field before saving it into the database
            $confirmationField = $attr.'_confirmation';
            if ($this->$confirmationField) {
                unset($this->$confirmationField);
            }
        }
    }

    /**
     * Initiate a mock expectation on the facade.
     *
     * @param string $name      name of the method being called
     * @param array  $arguments method arguments
     *
     * @return Expectation|void
     */
    public static function __callStatic($name, $arguments)
    {
        if ($name === 'shouldReceive') {
            $class = get_called_class();
            static::$mock[$class] = static::$mock[$class] ?? Mockery::mock();

            return static::$mock[$class]->shouldReceive(...$arguments);
        }
    }

    /**
     * Initiate a mock expectation that is specific for the save method.
     *
     * @return Expectation
     */
    public function shouldReceiveSave()
    {
        return $this->localMockShouldReceive('save');
    }

    /**
     * Initiate a mock expectation that is specific for the delete method.
     *
     * @return Expectation
     */
    public function shouldReceiveDelete()
    {
        return $this->localMockShouldReceive('delete');
    }

    /**
     * Check if local mock is set.
     *
     * @return bool
     */
    protected function hasLocalMock(): bool
    {
        return $this->localMock !== null;
    }

    /**
     * Get a local mock instance.
     *
     * @return Mockery\Mock|Mockery\MockInterface
     */
    protected function getLocalMock()
    {
        if (!$this->hasLocalMock()) {
            $this->localMock = Mockery::mock();
        }

        return $this->localMock;
    }

    /**
     * Initiate a mockery expectation that is specific for the given method.
     *
     * @param string $method name of the method being mocked
     *
     * @return Expectation
     */
    protected function localMockShouldReceive(string $method)
    {
        return $this->getLocalMock()->shouldReceive($method);
    }

    /**
     * Check for a expectation for given method on local mock.
     *
     * @param string $method name of the method being checked
     *
     * @return bool
     */
    protected function localMockHasExpectationsFor(string $method): bool
    {
        return $this->hasLocalMock() && $this->getLocalMock()->mockery_getExpectationsFor($method);
    }

    /**
     * Gets the first entity of this kind that matches the query.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in MongoDB query
     * @param bool  $useCache   retrieves the entity through a CacheableCursor
     *
     * @return ActiveRecord
     */
    public static function first(
        $query = [],
        array $projection = [],
        bool $useCache = false
    ) {
        return static::callMockOrParent('first', func_get_args());
    }

    /**
     * Gets the first entity of this kind that matches the query. If no
     * document was found, throws ModelNotFoundException.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     * @param bool  $useCache   retrieves the entity through a CacheableCursor
     *
     * @throws \Mongolid\Exception\ModelNotFoundException if no document was found
     *
     * @return ActiveRecord
     */
    public static function firstOrFail(
        $query = [],
        array $projection = [],
        bool $useCache = false
    ) {
        return static::callMockOrParent('firstOrFail', func_get_args());
    }

    /**
     * Gets the first entity of this kind that matches the query. If no
     * document was found, a new entity will be returned with the
     * _id field filled.
     *
     * @param mixed $id document id
     *
     * @return ActiveRecord
     */
    public static function firstOrNew($id)
    {
        return static::callMockOrParent('firstOrNew', func_get_args());
    }

    /**
     * Gets a cursor of this kind of entities that matches the query from the
     * database.
     *
     * @param array $query      mongoDB selection criteria
     * @param array $projection fields to project in MongoDB query
     * @param bool  $useCache   retrieves a CacheableCursor instead
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public static function where(
        array $query = [],
        array $projection = [],
        bool $useCache = false
    ) {
        return static::callMockOrParent('where', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public static function all()
    {
        return static::callMockOrParent('all', func_get_args());
    }

    /**
     * Calls mock method if its have expectations. Calls parent method otherwise.
     *
     * @param string $method    name of the method being called
     * @param array  $arguments arguments to pass in method call
     *
     * @return mixed See parent implementation
     */
    protected static function callMockOrParent(string $method, array $arguments)
    {
        $classToCall = 'parent';
        $class = get_called_class();
        $mock = static::$mock[$class] ?? null;

        if ($mock && $mock->mockery_getExpectationsFor($method)) {
            $classToCall = $mock;
        }

        return call_user_func_array([$classToCall, $method], $arguments);
    }
}
