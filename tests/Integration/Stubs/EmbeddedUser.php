<?php
namespace Mongolid\Laravel\Tests\Integration\Stubs;

use Mongolid\Laravel\AbstractModel;

class EmbeddedUser extends AbstractModel
{
    /**
     * @var string
     */
    protected $collection = 'users';

    /**
     * @var array
     */
    protected $timestamps = true;

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
