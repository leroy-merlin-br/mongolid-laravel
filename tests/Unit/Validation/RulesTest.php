<?php
namespace Mongolid\Laravel\Validation;

use InvalidArgumentException;
use Mockery as m;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use Mongolid\Connection\Connection;
use Mongolid\Laravel\TestCase;

class RulesTest extends TestCase
{
    public function testShouldBeUnique(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectDatabase('mongolid')
            ->andReturn($database);

        $database->expects()
            ->selectCollection('users')
            ->andReturn($collection);

        $collection->expects()
            ->count(['email' => 'john@doe.com'])
            ->andReturn(0);

        // Actions
        $result = $rules->unique('email', 'john@doe.com', ['users']);

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldBeUniqueExcludingId(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);
        $parameters = ['users', 'email_field', '5ba3a7c936e5eb038a118521'];

        $query = [
            'email_field' => 'john@doe.com',
            '_id' => ['$ne' => new ObjectId('5ba3a7c936e5eb038a118521')],
        ];

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectDatabase('mongolid')
            ->andReturn($database);

        $database->expects()
            ->selectCollection('users')
            ->andReturn($collection);

        $collection->expects()
            ->count($query)
            ->andReturn(0);

        // Actions
        $result = $rules->unique('email', 'john@doe.com', $parameters);

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldNotBeUniqueWhenThereAreResults(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);
        $parameters = ['users', 'email_field', '5ba3a7c936e5eb038a118521', 'parent'];

        $query = [
            'email_field' => 'john@doe.com',
            'parent' => ['$ne' => new ObjectId('5ba3a7c936e5eb038a118521')],
        ];

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectDatabase('mongolid')
            ->andReturn($database);

        $database->expects()
            ->selectCollection('users')
            ->andReturn($collection);

        $collection->expects()
            ->count($query)
            ->andReturn(1);

        // Actions
        $result = $rules->unique('email', 'john@doe.com', $parameters);

        // Assertions
        $this->assertFalse($result);
    }

    public function testShouldBeUniqueCastingIdToInt(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);
        $parameters = ['users', 'email_field', '88991122', 'id', 'true'];

        $query = [
            'email_field' => 'john@doe.com',
            'id' => ['$ne' => 88991122],
        ];

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectDatabase('mongolid')
            ->andReturn($database);

        $database->expects()
            ->selectCollection('users')
            ->andReturn($collection);

        $collection->expects()
            ->count($query)
            ->andReturn(0);

        // Actions
        $result = $rules->unique('email', 'john@doe.com', $parameters);

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldNotCastIdToIntIfParameterIsNotStringTrue(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);
        $parameters = ['users', 'email_field', '88991122', 'id', 'foo'];

        $query = [
            'email_field' => 'john@doe.com',
            'id' => ['$ne' => '88991122'],
        ];

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectDatabase('mongolid')
            ->andReturn($database);

        $database->expects()
            ->selectCollection('users')
            ->andReturn($collection);

        $collection->expects()
            ->count($query)
            ->andReturn(0);

        // Actions
        $result = $rules->unique('email', 'john@doe.com', $parameters);

        // Assertions
        $this->assertTrue($result);
    }

    public function testUniqueShouldValidateParameters(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        $parameters = [];

        // Expectations
        $connection->expects()
            ->getClient()
            ->never();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation rule mongolid_unique requires at least 1 parameters.');

        // Actions
        $rules->unique('email', 'john@doe.com', $parameters);
    }

    public function testShouldExist(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectDatabase('mongolid')
            ->andReturn($database);

        $database->expects()
            ->selectCollection('users')
            ->andReturn($collection);

        $collection->expects()
            ->count(['email' => 'john@doe.com'])
            ->andReturn(1);

        // Actions
        $result = $rules->exists('email', 'john@doe.com', ['users']);

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldExistWithIntValue(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectDatabase('mongolid')
            ->andReturn($database);

        $database->expects()
            ->selectCollection('users')
            ->andReturn($collection);

        $collection->expects()
            ->count(['user_id' => 1234])
            ->andReturn(1);

        // Actions
        $result = $rules->exists('user_id', 1234, ['users']);

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldNotExistWhenThereIsNoResults(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectDatabase('mongolid')
            ->andReturn($database);

        $database->expects()
            ->selectCollection('users')
            ->andReturn($collection);

        $collection->expects()
            ->count(['email_field' => 'john@doe.com'])
            ->andReturn(0);

        // Actions
        $result = $rules->exists('email', 'john@doe.com', ['users', 'email_field']);

        // Assertions
        $this->assertFalse($result);
    }

    public function testExistsShouldValidateParameters(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        $parameters = [];

        // Expectations
        $connection->expects()
            ->getClient()
            ->never();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation rule mongolid_exists requires at least 1 parameters.');

        // Actions
        $rules->exists('email', 'john@doe.com', $parameters);
    }

    public function testShouldRetrieveAlreadyTranslatedMessage(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);
        $message = 'The email should be unique yo';

        // Actions
        $result = $rules->message($message, 'email', 'mongolid_unique');

        // Assertions
        $this->assertSame($result, $message);
    }

    public function testShouldTranslateAMessage(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);
        $expectedMessage = 'The email has already been taken.';

        // Actions
        $result = $rules->message('validation.mongolid_unique', 'email', 'mongolid_unique');

        // Assertions
        $this->assertSame($result, $expectedMessage);
    }

    public function testShouldBeAnObjectIdWhenUsingObject(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        // Actions
        $result = $rules->objectId('_id', new ObjectId());

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldBeAnObjectIdWhenUsingString(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        // Actions
        $result = $rules->objectId('productBank', (string) (new ObjectId()));

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldNotBeAnObjectId(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);

        // Actions
        $result = $rules->objectId('productBank', '1234');

        // Assertions
        $this->assertFalse($result);
    }

    public function testShouldRetrieveAlreadyTranslatedMessageForObjectId(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);
        $message = 'You shall not pass';

        // Actions
        $result = $rules->objectIdMessage($message, '_id', 'object_id');

        // Assertions
        $this->assertSame($result, $message);
    }

    public function testShouldTranslateAMessageForObjectId(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $rules = new Rules($connection);
        $expectedMessage = 'The productBank must be an MongoDB ObjectId.';

        // Actions
        $result = $rules->objectIdMessage('validation.object_id', 'productBank', 'object_id');

        // Assertions
        $this->assertSame($result, $expectedMessage);
    }
}
