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
     * Get the Firebase claims mapping configuration.
     *
     * Override this method in your User model to customize claim mapping.
     * Format: ['model_attribute' => 'claim_key']
     *
     * @return array
     */
    protected function getFirebaseClaimsMapping(): array
    {
        if (property_exists($this, 'firebaseClaimsMapping')) {
            return $this->firebaseClaimsMapping;
        }

        return [
            'email' => 'email',
            'name' => 'name',
            'picture' => 'picture',
        ];
    }

    /**
     * Get the attribute configuration for matching existing users.
     *
     * Override this method or set the $firebaseResolveBy property in your User model.
     *
     * Formats:
     * - ['claim_key' => 'model_attribute'] - e.g., ['sub' => 'id'] or ['sub' => 'firebase_uid']
     * - 'attribute_name' - e.g., 'email' (uses same name for claim and model attribute)
     *
     * @return array|string
     */
    protected function getFirebaseResolveBy(): array|string
    {
        if (property_exists($this, 'firebaseResolveBy')) {
            return $this->firebaseResolveBy;
        }

        return ['sub' => 'id'];
    }

    /**
     * Get User by claim.
     *
     * @return self
     */
    public function resolveByClaims(array $claims): object
    {
        $resolveBy = $this->getFirebaseResolveBy();

        // Parse firebaseResolveBy to get claim key
        if (is_string($resolveBy)) {
            $claimKey = $resolveBy;
        } else {
            $claimKey = array_key_first($resolveBy);
        }

        $id = (string) $claims[$claimKey];
        $attributes = $this->transformClaims($claims);

        return $this->updateOrCreateUser($id, $attributes)->setClaims($claims);
    }

    /**
     * Update or create user.
     *
     * @param  int|string  $id
     * @return self
     */
    public function updateOrCreateUser($id, array $attributes): object
    {
        $resolveBy = $this->getFirebaseResolveBy();

        // Parse firebaseResolveBy to get model attribute
        if (is_string($resolveBy)) {
            $modelAttribute = $resolveBy;
        } else {
            $modelAttribute = array_values($resolveBy)[0];
        }

        // Try to find existing user by the configured attribute
        if ($user = $this->where($modelAttribute, $id)->first()) {
            $user->fill($attributes);

            if ($user->isDirty()) {
                $user->save();
            }

            return $user;
        }

        // Create new user
        $user = $this->fill($attributes);
        $user->$modelAttribute = $id;
        $user->save();

        return $user;
    }

    /**
     * Transform claims to attributes.
     */
    public function transformClaims(array $claims): array
    {
        $attributes = [];

        foreach ($this->getFirebaseClaimsMapping() as $attribute => $claimKey) {
            if (! empty($claims[$claimKey])) {
                $attributes[$attribute] = (string) $claims[$claimKey];
            }
        }

        return $attributes;
    }

    /**
     * Set firebase token.
     *
     * @param  string  $token
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
     * Set claims from JWT token.
     *
     * @param  array  $claims
     * @return self
     */
    public function setClaims(array $claims)
    {
        $this->claims = $claims;

        return $this;
    }

    /**
     * Get claims from JWT token.
     *
     * @return array
     */
    public function getClaims()
    {
        return $this->claims ?? [];
    }

    /**
     * Check if user is anonymous.
     *
     * @return bool
     */
    public function isAnonymous()
    {
        $claims = $this->getClaims();

        return isset($claims['firebase']['sign_in_provider'])
            && $claims['firebase']['sign_in_provider'] === 'anonymous';
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
     * @param  string  $value
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
