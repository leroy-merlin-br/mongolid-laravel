<?php
namespace Mongolid\Laravel\Tests\Integration\Stubs;

use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\Container\Ioc;
use Mongolid\Laravel\Model;

class User extends Model
{
    /**
     * @var string
     */
    protected $collection = 'users';

    /**
     * @var array
     */
    protected $fields = [
        '_id' => 'objectId',
    ];

    public function collection(): Collection
    {
        $connection = Ioc::make(Connection::class);
        $client = $connection->getRawConnection();

        return $client->{$connection->defaultDatabase}->{$this->collection};
    }

    public function parent()
    {
        return $this->referencesOne(User::class, 'parent');
    }

    public function siblings()
    {
        return $this->referencesMany(User::class, 'siblings');
    }
}
