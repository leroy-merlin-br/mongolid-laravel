<?php
namespace Zizaco\MongolidLaravel;

use Illuminate\Auth\UserProviderInterface;
use Illuminate\Hashing\HasherInterface;
use Illuminate\Auth\UserInterface;

class MongoLidUserProvider implements UserProviderInterface
{
    /**
     * The hasher implementation.
     *
     * @var \Illuminate\Hashing\HasherInterface
     */
    protected $hasher;

    /**
     * The MongoLid user model.
     *
     * @var string
     */
    protected $model;

    /**
     * Create a new database user provider.
     *
     * @param  \Illuminate\Hashing\HasherInterface  $hasher
     * @param  string  $model
     * @return void
     */
    public function __construct(HasherInterface $hasher, $model)
    {
        $this->model = $model;
        $this->hasher = $hasher;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveByID($identifier)
    {
        return $this->createModel()->first($identifier);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        unset($credentials['password']);

        return $this->createModel()->first($credentials);
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Auth\UserInterface  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserInterface $user, array $credentials)
    {
        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * Create a new instance of the model.
     *
     * @return Zizaco\MongolidLaravel\MongoLid
     */
    public function createModel()
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class;
    }
}