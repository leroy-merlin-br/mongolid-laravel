<?php

namespace MongolidLaravel\Validation;

use InvalidArgumentException;
use Mockery as m;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use MongolidLaravel\TestCase;

class RulesTest extends TestCase
{
    public function testShouldBeUnique()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
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

    public function testShouldBeUniqueExcludingId()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);
        $parameters = ['users', 'email_field', '5ba3a7c936e5eb038a118521'];

        $query = [
            'email_field' => 'john@doe.com',
            '_id' => ['$ne' => new ObjectId('5ba3a7c936e5eb038a118521')],
        ];

        // Expectations
        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
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

    public function testShouldNotBeUniqueWhenThereAreResults()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);
        $parameters = ['users', 'email_field', '5ba3a7c936e5eb038a118521', 'parent'];

        $query = [
            'email_field' => 'john@doe.com',
            'parent' => ['$ne' => new ObjectId('5ba3a7c936e5eb038a118521')],
        ];

        // Expectations
        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
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

    public function testUniqueShouldValidateParameters()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);

        $parameters = [];

        // Expectations
        $pool->expects()
            ->getConnection()
            ->never();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation rule mongolid_unique requires at least 1 parameters.');

        // Actions
        $rules->unique('email', 'john@doe.com', $parameters);
    }

    public function testShouldExist()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
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

    public function testShouldNotExistWhenThereIsNoResults()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
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

    public function testExistsShouldValidateParameters()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);

        $parameters = [];

        // Expectations
        $pool->expects()
            ->getConnection()
            ->never();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation rule mongolid_exists requires at least 1 parameters.');

        // Actions
        $rules->exists('email', 'john@doe.com', $parameters);
    }

    public function testShouldRetrieveAlreadyTranslatedMessage()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);
        $message = 'The email should be unique yo';

        // Actions
        $result = $rules->message($message, 'email', 'mongolid_unique');

        // Assertions
        $this->assertSame($result, $message);
    }

    public function testShouldTranslateAMessage()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);
        $expectedMessage = 'The email has already been taken.';

        // Actions
        $result = $rules->message('validation.mongolid_unique', 'email', 'mongolid_unique');

        // Assertions
        $this->assertSame($result, $expectedMessage);
    }

    public function testShouldBeAnObjectIdWhenUsingObject()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);

        // Actions
        $result = $rules->objectId('_id', new ObjectId());

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldBeAnObjectIdWhenUsingString()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);

        // Actions
        $result = $rules->objectId('productBank', (string) (new ObjectId()));

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldNotBeAnObjectId()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);

        // Actions
        $result = $rules->objectId('productBank', '1234');

        // Assertions
        $this->assertFalse($result);
    }

    public function testShouldRetrieveAlreadyTranslatedMessageForObjectId()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);
        $message = 'You shall not pass';

        // Actions
        $result = $rules->objectIdMessage($message, '_id', 'object_id');

        // Assertions
        $this->assertSame($result, $message);
    }

    public function testShouldTranslateAMessageForObjectId()
    {
        // Set
        $pool = m::mock(Pool::class);
        $rules = new Rules($pool);
        $expectedMessage = 'The productBank must be an MongoDB ObjectId.';

        // Actions
        $result = $rules->objectIdMessage('validation.object_id', 'productBank', 'object_id');

        // Assertions
        $this->assertSame($result, $expectedMessage);
    }
}
