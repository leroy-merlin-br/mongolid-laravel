<?php
namespace Zizaco\MongolidLaravel;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class MongoLidUserProvider implements UserProvider
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
     */
    public function __construct(HasherContract $hasher, $model)
    {
        $this->model  = $model;
        $this->hasher = $hasher;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     *
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveByID($identifier)
    {
        return $this->createModel()->first($identifier);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     *
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        unset($credentials['password']);

        return $this->createModel()->first($credentials);
    }

    /**
     * Validate a user against the given credentials.

     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
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
        $class = '\\' . ltrim($this->model, '\\');

        return new $class;
    }

    /**
     * Retrieve a user by by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string $token
     *
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveByToken($identifier, $token)
    {
        $user = $this->createModel()->first(
            ['_id' => $identifier, 'remember_token' => $token]
        );

        if ($user) {
            return $user;
        }
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @return void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
        $user->remember_token = $token;
        $user->save();
    }
}
