<?php
namespace Mongolid\Laravel\Tests\Integration\Stubs;

use Mongolid\Laravel\AbstractModel;

class ReferencedUser extends AbstractModel
{
    /**
     * @var string
     */
    protected $collection = 'users';

    /**
     * @var array
     */
    protected $timestamps = false;

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
