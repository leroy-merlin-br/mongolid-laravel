<?php
namespace Mongolid\Laravel\Tests\Integration\Stubs;

use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\Container\Ioc;
use Mongolid\Laravel\Model;

class EmbeddedUser extends Model
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
        'created_at' => 'createdAtTimestamp',
    ];

    public function collection(): Collection
    {
        $connection = Ioc::make(Connection::class);
        $client = $connection->getRawConnection();

        return $client->{$connection->defaultDatabase}->{$this->collection};
    }

    public function parent()
    {
        return $this->embedsOne(EmbeddedUser::class);
    }

    public function siblings()
    {
        return $this->embedsMany(EmbeddedUser::class);
    }

    public function son()
    {
        return $this->embedsOne(EmbeddedUser::class, 'arbitrary_field');
    }

    public function grandsons()
    {
        return $this->embedsMany(EmbeddedUser::class, 'other_arbitrary_field');
    }

    public function sameName()
    {
        $this->embedsOne(EmbeddedUser::class, 'sameName');
    }
}
