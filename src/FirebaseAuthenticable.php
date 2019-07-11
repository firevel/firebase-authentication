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
     * Get User by claim.
     *
     * @param array $claims
     * @return User
     */
    public static function resolveByClaims($claims)
    {
        $id = (string) $claims['sub'];

        if ($user = self::find($id)) {
            return $user;
        }

        $user = self::make()->fill(self::transformClaims($claims));
        $user->id = $id;
        $user->save();

        return $user;
    }

    /**
     * Transform claims to attributes.
     *
     * @param array $claims
     * @return array
     */
    public static function transformClaims($claims)
    {
        $attributes = [
            'id' => (string) $claims['sub'],
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