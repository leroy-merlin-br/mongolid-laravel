<?php
namespace Mongolid\Laravel\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Mockery as m;
use MongoDB\BSON\ObjectID;
use Mongolid\Laravel\AbstractModel;
use Mongolid\Laravel\TestCase;

class UserProviderTest extends TestCase
{
    public function testShouldRetrieveById(): void
    {
        // Set
        $provider = $this->getProvider();

        $params = ['_id' => new ObjectID()];

        // Actions
        $result = $provider->retrieveByID($params);

        // Assertions
        $this->assertInstanceOf(AbstractModel::class, $result);
    }

    public function testShouldRetrieveByCredentials(): void
    {
        // Set
        $provider = $this->getProvider();

        $params = ['_id' => new ObjectID(), 'password' => '1234'];

        // Actions
        $result = $provider->retrieveByCredentials($params);

        // Assertions
        $this->assertInstanceOf(AbstractModel::class, $result);
    }

    public function testShouldValidateCredentials(): void
    {
        // Set
        $provider = $this->getProvider();
        $user = m::mock(Authenticatable::class);
        $params = ['user' => 'user', 'password' => '1234'];
        $hasher = $this->app->make(Hasher::class);

        // Expectations
        $user->expects()
            ->getAuthPassword()
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
        $this->assertInstanceOf(AbstractModel::class, $result);
    }

    public function testShouldNotRetrieveByToken(): void
    {
        // Set
        $model = new class() extends AbstractModel
        {
            public static function first($query = [], array $projection = [])
            {
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
        $user = m::mock(Authenticatable::class);

        // Expectations
        $user->expects()
            ->save()
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
        $model = new class() extends AbstractModel
        {
            public static function first($query = [], array $projection = [])
            {
                return m::mock(AbstractModel::class);
            }
        };

        $hasher = $this->app->make(Hasher::class);
        return new MongolidUserProvider($hasher, get_class($model));
    }
}
