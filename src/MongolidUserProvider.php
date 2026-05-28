<?php

namespace MongolidLaravel;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Mongolid\Container\Container;
use SensitiveParameter;

class MongolidUserProvider implements UserProvider
{
    /**
     * The hasher implementation.
     */
    protected HasherContract $hasher;

    /**
     * The MongoLid user model.
     */
    protected string $model;

    /**
     * Create a new database user provider.
     *
     * @param string $model
     * Class::class instanceof \MongolidLaravel\MongoLidModel
     */
    public function __construct(HasherContract $hasher, string $model)
    {
        $this->model = $model;
        $this->hasher = $hasher;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param mixed $identifier
     *
     * @return UserContract|null
     * @throws BindingResolutionException
     */
    public function retrieveByID($identifier)
    {
        /** @var UserContract|null $user */
        $user = $this->createModel()->first($identifier);

        return $user;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     *
     * @return UserContract|null
     * @throws BindingResolutionException
     */
    public function retrieveByCredentials(#[SensitiveParameter] array $credentials)
    {
        unset($credentials['password']);

        /** @var UserContract|null $user */
        $user = $this->createModel()->first($credentials);

        return $user;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param UserContract $user
     * @param array        $credentials
     *
     * @return bool
     */
    public function validateCredentials(
        UserContract $user,
        #[SensitiveParameter] array $credentials
    ): bool {
        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param mixed  $identifier
     * @param string $token
     *
     * @return UserContract|null
     * @throws BindingResolutionException
     */
    public function retrieveByToken($identifier, $token)
    {
        /** @var UserContract|null $user */
        $user = $this->createModel()->first(
            ['_id' => $identifier, 'remember_token' => $token]
        );

        return $user;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param string $token
     */
    public function updateRememberToken(
        UserContract $user,
        $token
    ): void {
        $user->setRememberToken($token);
        $user->save();
    }

    /**
     * {@inheritDoc}
     */
    public function rehashPasswordIfRequired(
        UserContract $user,
        #[SensitiveParameter] array $credentials,
        bool $force = false
    ): void {
        $needsRehash = $this->hasher->needsRehash(
            $user->getAuthPassword()
        );

        if (!$needsRehash && !$force) {
            return;
        }

        $user->forceFill([
            $user->getAuthPasswordName() => $this->hasher->make(
                $credentials['password']
            ),
        ])->save();
    }

    /**
     * Create a new instance of the model.
     *
     * @return MongoLidModel
     * @throws BindingResolutionException
     */
    protected function createModel(): MongoLidModel|LegacyMongolidModel
    {
        $class = '\\' . ltrim($this->model, '\\');

        return Container::make($class);
    }
}
