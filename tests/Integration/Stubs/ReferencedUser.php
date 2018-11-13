<?php
namespace Mongolid\Laravel\Tests\Integration\Stubs;

use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\Container\Ioc;
use Mongolid\Laravel\Model;

class ReferencedUser extends Model
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
        return $this->referencesOne(ReferencedUser::class);
    }

    public function siblings()
    {
        return $this->referencesMany(ReferencedUser::class);
    }

    public function son()
    {
        return $this->referencesOne(ReferencedUser::class, 'arbitrary_field', 'code');
    }

    public function grandsons()
    {
        return $this->referencesMany(ReferencedUser::class, null, 'code');
    }

    public function invalid()
    {
        return 'I am not a relation!';
    }
}
