<?php

namespace MongolidLaravel;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Auth\User;
use Mockery as m;
use MongoDB\BSON\ObjectID;

class MongolidUserProviderTest extends TestCase
{
    private Hasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = m::mock($this->app->make(Hasher::class))
            ->makePartial();
    }

    public function testShouldRetrieveById(): void
    {
        // Set
        $provider = $this->getProvider();

        $params = ['_id' => new ObjectID()];

        // Actions
        $result = $provider->retrieveByID($params);

        // Assertions
        $this->assertInstanceOf(MongolidModel::class, $result);
    }

    public function testShouldRetrieveByCredentials(): void
    {
        // Set
        $provider = $this->getProvider();

        $params = ['_id' => new ObjectID(), 'password' => '1234'];

        // Actions
        $result = $provider->retrieveByCredentials($params);

        // Assertions
        $this->assertInstanceOf(MongolidModel::class, $result);
    }

    public function testShouldValidateCredentials(): void
    {
        // Set
        $provider = $this->getProvider();
        $user = m::mock(User::class);
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

    public function testShouldRetrieveByToken(): void
    {
        // Set
        $provider = $this->getProvider();

        // Actions
        $result = $provider->retrieveByToken('1234', '4321');

        // Assertions
        $this->assertInstanceOf(MongolidModel::class, $result);
    }

    public function testShouldNotRetrieveByToken(): void
    {
        // Set
        $model = new class () extends MongolidModel {
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

    public function testShouldUpdateRememberToken(): void
    {
        // Set
        $provider = $this->getProvider();
        $user = m::mock(User::class)->makePartial();

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

    public function testShouldRehashPasswordIfRequired(): void
    {
        // Set
        $provider = $this->getProvider();
        $user = m::mock(User::class)->makePartial();
        $credentials = [
            'password' => '1234',
        ];

        // Expectations
        $user->expects()
            ->getAuthPassword()
            ->andReturn($credentials['password']);

        $user->expects()
            ->getAuthPasswordName()
            ->andReturn('password');

        $user->expects('forceFill')
            ->withArgs(function ($attributes) use ($credentials) {
                $result = $this->hasher->check(
                    $credentials['password'],
                    $attributes['password']
                );

                $this->assertTrue($result);

                return true;
            })
            ->andReturnSelf();

        $user->expects()
            ->save()
            ->andReturnTrue();

        // Actions
        $provider->rehashPasswordIfRequired($user, $credentials);
    }

    public function testShouldRehashPasswordIfRequired3(): void
    {
        // Set
        $provider = $this->getProvider();
        $user = m::mock(User::class)->makePartial();
        $credentials = [
            'password' => '1234',
        ];

        // Expectations
        $user->expects()
            ->getAuthPassword()
            ->andReturn($credentials['password']);

        $this->hasher->expects('needsRehash')
            ->andReturnFalse();

        $user->expects('forceFill')
            ->never();

        $user->expects()
            ->save()
            ->never();

        // Actions
        $provider->rehashPasswordIfRequired($user, $credentials);
    }

    protected function getProvider(): MongolidUserProvider
    {
        $model = new class () extends MongolidModel {
            public static function first(
                $query = [],
                array $projection = [],
                bool $useCache = false
            ) {
                return m::mock(MongolidModel::class);
            }
        };


        return new MongolidUserProvider($this->hasher, get_class($model));
    }
}
