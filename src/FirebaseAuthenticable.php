<?php

namespace Firevel\FirebaseAuthentication;

trait FirebaseAuthenticable
{
    /**
     * The claims decoded from the JWT token.
     *
     * @var array
     */
    protected $claims;

    /**
     * Firebase token.
     *
     * @var string|null
     */
    protected $firebaseAuthenticationToken;

    /**
     * Get User by claim.
     *
     * @param array $claims
     *
     * @return self
     */
    public function resolveByClaims(array $claims): object
    {
        $id = (string) $claims['sub'];

        $attributes = $this->transformClaims($claims);

        return $this->updateOrCreateUser($id, $attributes);
    }

    /**
     * Update or create user.
     *
     * @param int|string $id
     * @param array      $attributes
     *
     * @return self
     */
    public function updateOrCreateUser($id, array $attributes): object
    {
        if ($user = $this->find($id)) {
            $user
                ->fill($attributes);

            if ($user->isDirty()) {
                $user->save();
            }

            return $user;
        }

        $user = $this->fill($attributes);
        $user->id = $id;
        $user->save();

        return $user;
    }

    /**
     * Transform claims to attributes.
     *
     * @param array $claims
     *
     * @return array
     */
    public function transformClaims(array $claims): array
    {
        $attributes = [
            'email' => (string) $claims['email'],
        ];

        if (!empty($claims['name'])) {
            $attributes['name'] = (string) $claims['name'];
        }

        if (!empty($claims['picture'])) {
            $attributes['picture'] = (string) $claims['picture'];
        }

        return $attributes;
    }

    /**
     * Set firebase token.
     *
     * @param string $token
     *
     * @return self
     */
    public function setFirebaseAuthenticationToken($token)
    {
        $this->firebaseAuthenticationToken = $token;

        return $this;
    }

    /**
     * Get firebase token.
     *
     * @return string
     */
    public function getFirebaseAuthenticationToken()
    {
        return $this->firebaseAuthenticationToken;
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->id;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        throw new \Exception('No password support for Firebase Users');
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        throw new \Exception('No remember token support for Firebase Users');
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param string $value
     *
     * @return void
     */
    public function setRememberToken($value)
    {
        throw new \Exception('No remember token support for Firebase User');
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        throw new \Exception('No remember token support for Firebase User');
    }
}
