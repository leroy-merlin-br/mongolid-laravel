<?php

namespace MongolidLaravel;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Mockery as m;
use MongoDB\BSON\ObjectID;
use TestCase;

class MongolidUserProviderTest extends TestCase
{
    public function testShouldRetrieveById()
    {
        // Set
        $provider = $this->getProvider();

        $params = ['_id' => new ObjectID()];

        // Actions
        $result = $provider->retrieveByID($params);

        // Assertions
        $this->assertInstanceOf(MongolidModel::class, $result);
    }

    public function testShouldRetrieveByCredentials()
    {
        // Set
        $provider = $this->getProvider();

        $params = ['_id' => new ObjectID(), 'password' => '1234'];

        // Actions
        $result = $provider->retrieveByCredentials($params);

        // Assertions
        $this->assertInstanceOf(MongolidModel::class, $result);
    }

    public function testShouldValidateCredentials()
    {
        // Set
        $provider = $this->getProvider();
        $user = m::mock(Authenticatable::class);
        $params = ['user' => 'user', 'password' => '1234'];
        $hasher = $this->app->make(Hasher::class);

        // Expectations
        $user->shouldReceive('getAuthPassword')
            ->once()
            ->with()
            ->andReturn($hasher->make('1234'));

        // Actions
        $result = $provider->validateCredentials($user, $params);

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldRetrieveByToken()
    {
        // Set
        $provider = $this->getProvider();

        // Actions
        $result = $provider->retrieveByToken('1234', '4321');

        // Assertions
        $this->assertInstanceOf(MongolidModel::class, $result);
    }

    public function testShouldNotRetrieveByToken()
    {
        // Set
        $model = new class() extends MongolidModel {
            public static function first(
                $query = [],
                array $projection = [],
                bool $useCache = false
            ) {
            }
        };

        $hasher = $this->app->make(Hasher::class);
        $provider = new MongolidUserProvider($hasher, get_class($model));

        // Actions
        $result = $provider->retrieveByToken('1234', '4321');

        // Assertions
        $this->assertNull($result);
    }

    public function testShouldUpdateRememberToken()
    {
        // Set
        $provider = $this->getProvider();
        $user = m::mock(Authenticatable::class);

        // Expectations
        $user->shouldReceive('save')
            ->once()
            ->with()
            ->andReturn(true);

        // Actions
        $provider->updateRememberToken($user, '1234');

        // Assertions
        $this->assertEquals('1234', $user->remember_token);
    }

    /**
     * @return MongolidUserProvider
     */
    protected function getProvider()
    {
        $model = new class() extends MongolidModel {
            public static function first(
                $query = [],
                array $projection = [],
                bool $useCache = false
            ) {
                return m::mock(MongolidModel::class);
            }
        };

        $hasher = $this->app->make(Hasher::class);
        $provider = new MongolidUserProvider($hasher, get_class($model));

        return $provider;
    }
}
