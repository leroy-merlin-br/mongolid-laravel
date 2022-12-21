<?php

namespace MongolidLaravel;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\MessageBag;
use Mockery;
use Mockery\Expectation;
use MongoDB\Collection;
use Mongolid\Cursor\CursorInterface;
use Mongolid\LegacyRecord;

/**
 * This class extends the Mongolid\LegacyRecord, so, in order
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
 */
abstract class LegacyMongolidModel extends LegacyRecord
{
    /**
     * Public local mock.
     *
     * @var Mockery\Mock
     */
    public $localMock;

    /**
     * Public static mock.
     *
     * @var Mockery\Mock
     */
    public static $mock;

    /**
     * Validation rules.
     *
     * @var mixed[]
     */
    protected $rules;

    /**
     * Error message bag.
     *
     * @var MessageBag
     */
    protected $errors;

    /**
     * List of attribute names which should be hashed on save. For
     * example: array('password');.
     *
     * @var string[]
     */
    protected $hashedAttributes = [];

    /**
     * Save the model to the database if it's valid. This method also
     * checks for the presence of the localMock in order to call the save
     * method into the existing Mock in order not to touch the database.
     *
     * @param bool $force force save even if the object is invalid
     */
    public function save(bool $force = false): bool
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
    public function update(): bool
    {
        $this->hashAttributes();

        return parent::update();
    }

    /**
     * Overwrites the delete method in order to be able to check for
     * the expectation in the localMock in order to call the delete method
     * into the existing mock and avoid touching the database.
     */
    public function delete(): bool
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
        if (!$rules = $this->rules()) {
            return true;
        }

        $attributes = $this->getDocumentAttributes();

        // Verify attributes that are hashed and that have not changed
        // those doesn't need to be validated.
        foreach ($this->hashedAttributes as $hashedAttr) {
            if (isset($this->original[$hashedAttr]) && $this->$hashedAttr == $this->original[$hashedAttr]) {
                unset($rules[$hashedAttr]);
            }
        }

        // Creates validator with attributes and the rules of the object
        $validator = app(ValidationFactory::class)->make(
            $attributes,
            $rules,
            $this->messages()
        );

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
     * Get the contents of rules attribute.
     */
    public function rules(): array
    {
        return $this->rules ?? [];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [];
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
     * Gets the first entity of this kind that matches the query.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in MongoDB query
     * @param bool  $useCache   retrieves the entity through a CacheableCursor
     *
     * @return LegacyRecord
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
     * @throws \Mongolid\Model\Exception\ModelNotFoundException If no document was found
     *
     * @return LegacyRecord
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
     * @return LegacyRecord
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
     */
    public static function where(
        array $query = [],
        array $projection = [],
        bool $useCache = false
    ): CursorInterface {
        return static::callMockOrParent('where', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public static function all(): CursorInterface
    {
        return static::callMockOrParent('all', func_get_args());
    }

    /**
     * Returns the Mongo collection object.
     */
    protected function collection(): Collection
    {
        return $this->getCollection();
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
            $confirmationField = $attr . '_confirmation';
            if ($this->$confirmationField) {
                unset($this->$confirmationField);
            }
        }
    }

    /**
     * Check if local mock is set.
     */
    protected function hasLocalMock(): bool
    {
        return null !== $this->localMock;
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
     */
    protected function localMockHasExpectationsFor(string $method): bool
    {
        return $this->hasLocalMock() && $this->getLocalMock()->mockery_getExpectationsFor(
            $method
        );
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
        $class = static::class;
        $mock = static::$mock[$class] ?? null;

        if ($mock && $mock->mockery_getExpectationsFor($method)) {
            $classToCall = $mock;
        }

        return call_user_func_array([$classToCall, $method], $arguments);
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
        if ('shouldReceive' === $name) {
            $class = static::class;
            static::$mock[$class] = static::$mock[$class] ?? Mockery::mock();

            return static::$mock[$class]->shouldReceive(...$arguments);
        }
    }
}
