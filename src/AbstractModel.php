<?php
namespace Mongolid\Laravel;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\MessageBag;
use Mockery;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Model\AbstractModel as BaseModel;

/**
 * This class extends the Mongolid\Model\AbstractModel, so, in order
 * to understand the ODM implementation make sure to check the
 * base class.
 *
 * The Mongolid\Laravel\AbstractModel simply extends the original
 * and framework agnostic model of MongoLid and implements some
 * validation rules using Laravel validation components.
 *
 * Remember, this package is meant to be used with Laravel while
 * the "leroy-merlin\mongolid" is meant to be used with other frameworks
 * or even without any.
 *
 * @see AbstractModel
 *
 * @method static AbstractModel|\Mockery\ExpectationInterface|\Mockery\HigherOrderMessage shouldReceive(...$arguments)
 * @method static AbstractModel|\Mockery\ExpectationInterface|\Mockery\HigherOrderMessage expects(...$arguments)
 * @method static AbstractModel|\Mockery\ExpectationInterface|\Mockery\HigherOrderMessage allows(...$arguments)
 */
abstract class AbstractModel extends BaseModel
{
    /**
     * Public static mock.
     *
     * @var Mockery\Mock
     */
    protected static $mock;

    /**
     * Validation rules.
     *
     * @var array
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
     * @var array
     */
    protected $hashedAttributes = [];

    /**
     * Initiate a mock expectation on the facade.
     *
     * @param string $name      name of the method being called
     * @param array  $arguments method arguments
     *
     * @return \Mockery\ExpectationInterface|\Mockery\HigherOrderMessage|null
     */
    public static function __callStatic($name, $arguments)
    {
        if (in_array($name, ['shouldReceive', 'expects', 'allows'])) {
            $class = static::class;
            static::$mock[$class] = static::$mock[$class] ?? Mockery::mock();

            return static::$mock[$class]->{$name}(...$arguments);
        }
    }

    /**
     * Gets the first entity of this kind that matches the query.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in MongoDB query
     *
     * @return AbstractModel
     */
    public static function first($query = [], array $projection = [])
    {
        return static::callMockOrParent('first', func_get_args());
    }

    /**
     * Gets the first entity of this kind that matches the query. If no
     * document was found, throws ModelNotFoundException.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     *
     * @throws \Mongolid\Model\Exception\ModelNotFoundException If no document was found
     *
     * @return AbstractModel
     */
    public static function firstOrFail($query = [], array $projection = [])
    {
        return static::callMockOrParent('firstOrFail', func_get_args());
    }

    /**
     * Gets the first entity of this kind that matches the query. If no
     * document was found, a new entity will be returned with the
     * _id field filled.
     *
     * @param mixed $id document id
     *
     * @return AbstractModel
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
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public static function where(array $query = [], array $projection = []): CursorInterface
    {
        return static::callMockOrParent('where', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public static function all(...$arguments): CursorInterface
    {
        return static::callMockOrParent('all', $arguments);
    }

    /**
     * Clear created mocks.
     */
    public static function clearMocks(): void
    {
        static::$mock = [];
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
        $mock = static::$mock[static::class] ?? null;

        if ($mock && $mock->mockery_getExpectationsFor($method)) {
            return $mock->{$method}(...$arguments);
        }

        return parent::{$method}(...$arguments);
    }

    /**
     * Save the model to the database if it's valid.
     *
     * @param bool $force force save even if the object is invalid
     */
    public function save(bool $force = false): bool
    {
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
     * Verify if the model is valid by running its validation rules,
     * defined on attribute `$rules`.
     */
    public function isValid(): bool
    {
        if (!$rules = $this->rules()) {
            return true;
        }

        $attributes = $this->getDocumentAttributes();

        // Verify attributes that are hashed and that have not changed
        // those doesn't need to be validated.
        foreach ($this->hashedAttributes as $hashedAttr) {
            $originalAttributes = $this->getOriginalDocumentAttributes();
            if (isset($originalAttributes[$hashedAttr]) && $this->$hashedAttr == $originalAttributes[$hashedAttr]) {
                unset($rules[$hashedAttr]);
            }
        }

        // Creates validator with attributes and the rules of the object
        $validator = app(ValidationFactory::class)->make($attributes, $rules, $this->messages());

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
     * Hashes the attributes specified in the hashedAttributes
     * array.
     */
    protected function hashAttributes(): void
    {
        foreach ($this->hashedAttributes as $attr) {
            // Hash attribute if changed
            $originalAttributes = $this->getOriginalDocumentAttributes();
            if (!isset($originalAttributes[$attr]) || $this->$attr != $originalAttributes[$attr]) {
                $this->$attr = app(Hasher::class)->make($this->$attr);
            }

            // Removes any confirmation field before saving it into the database
            $confirmationField = $attr.'_confirmation';
            if ($this->$confirmationField) {
                unset($this->$confirmationField);
            }
        }
    }
}
