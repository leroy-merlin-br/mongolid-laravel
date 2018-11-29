<?php
namespace Mongolid\Laravel\Validation;

use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use Mongolid\Connection\Connection;
use Mongolid\Util\ObjectIdUtils;

class Rules
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * mongolid_unique:collection,field?,except?,idField?,isInt?
     *
     * @example Using attribute name as query field
     *   'email' => mongolid_unique:users
     * @example Using other query field
     *   'email' => mongolid_unique:users,user_email
     * @example Excluding itself from verification
     *   'email' => mongolid_unique:users,email,5ba3bc0836e5eb03f12a3c31
     * @example Excluding itself from verification using other field for Id
     *   'email' => mongolid_unique:users,email,5ba3bc0836e5eb03f12a3c31,user_id
     *
     * @example Excluding itself from verification using other field for Id and casting Id to int
     *   'email' => mongolid_unique:users,email,5ba3bc0836e5eb03f12a3c31,user_id,true
     *
     * @see https://laravel.com/docs/5.6/validation#rule-unique
     */
    public function unique(string $attribute, $value, array $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'mongolid_unique');
        $collection = $parameters[0];
        $field = $parameters[1] ?? $attribute;
        $query = [$field => $this->transformIfId($value)];

        if ($except = $parameters[2] ?? false) {
            $idColumn = $parameters[3] ?? '_id';

            if ('true' === ($parameters[4] ?? false)) {
                $except = (int) $except;
            }

            $query += [$idColumn => ['$ne' => $this->transformIfId($except)]];
        }

        return !$this->hasResults($collection, $query);
    }

    /**
     * mongolid_exists:collection,field?
     *
     * @example Using attribute name as query field
     *   'email' => mongolid_exists:users
     * @example Using other query field
     *   'email' => mongolid_exists:users,user_email
     *
     * @see https://laravel.com/docs/5.6/validation#rule-exists
     */
    public function exists(string $attribute, $value, array $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'mongolid_exists');
        $collection = $parameters[0];
        $field = $parameters[1] ?? $attribute;

        return $this->hasResults($collection, [$field => $this->transformIfId($value)]);
    }

    /**
     * Error message with fallback from Laravel rules 'unique' and 'exists'.
     */
    public function message(string $message, string $attribute, string $rule): string
    {
        if ("validation.{$rule}" !== $message) {
            return $message;
        }

        return $this->getTranslatedMessageFallback(
            str_replace('mongolid_', '', $rule),
            $attribute
        );
    }

    /**
     * Given attribute should be an ObjectId
     * object_id.
     *
     * @example Using attribute name as query field
     *   'product_id' => object_id
     *
     * @see ObjectId
     */
    public function objectId(string $attribute, $value)
    {
        return $this->isObjectId($value);
    }

    /**
     * Given attribute should be an ObjectId
     * object_id.
     *
     * @see ObjectId
     */
    public function objectIdMessage(string $message, string $attribute, string $rule)
    {
        if ("validation.{$rule}" !== $message) {
            return $message;
        }

        return "The {$attribute} must be an MongoDB ObjectId.";
    }

    /**
     * Require a certain number of parameters to be present.
     *
     * @throws InvalidArgumentException
     */
    private function requireParameterCount(int $count, array $parameters, string $rule): void
    {
        if (count($parameters) < $count) {
            throw new InvalidArgumentException("Validation rule {$rule} requires at least {$count} parameters.");
        }
    }

    /**
     * Run query on database and check for a result count.
     */
    private function hasResults(string $collection, array $query): bool
    {
        $connection = $this->connection->getClient();
        $database = $this->connection->defaultDatabase;

        return (bool) $connection->{$database}->{$collection}->count($query);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function transformIfId($value)
    {
        if ($value && $this->isObjectId($value)) {
            $value = new ObjectId($value);
        }

        return $value;
    }

    /**
     * If the user has not created a translation for 'mongolid_unique' rule,
     * this method will attempt to get the translation for 'unique' rule.
     * The same with 'mongolid_exists' and 'exists'.
     * Since it is not easy to use the framework to make this message, this
     * is a simple approach.
     */
    private function getTranslatedMessageFallback(string $rule, string $attribute): string
    {
        $attributeKey = "validation.attributes.$attribute";
        $attributeTranslation = trans($attributeKey);

        $attribute = $attributeTranslation === $attributeKey ? $attribute : $attributeTranslation;

        return trans("validation.{$rule}", compact('attribute'));
    }

    /**
     * @param mixed $value
     */
    private function isObjectId($value): bool
    {
        return ObjectIdUtils::isObjectId($value);
    }
}
