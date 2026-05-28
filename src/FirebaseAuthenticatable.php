<?php

namespace Firevel\FirebaseAuthentication;

use Firevel\FirebaseAuthentication\Events\FirebaseUserCreated;
use Firevel\FirebaseAuthentication\Events\FirebaseUserResolved;
use Firevel\FirebaseAuthentication\Events\FirebaseUserUpdated;

trait FirebaseAuthenticatable
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
     */
    protected function getFirebaseClaimsMapping(): array
    {
        if (property_exists($this, 'firebaseClaimsMapping')) {
            return $this->firebaseClaimsMapping;
        }

        return [
            'email' => 'email',
            'name' => 'name',
            'avatar_url' => 'picture',
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
     */
    protected function getFirebaseResolveBy(): array|string
    {
        if (property_exists($this, 'firebaseResolveBy')) {
            return $this->firebaseResolveBy;
        }

        return ['sub' => 'firebase_id'];
    }

    /**
     * Resolve (and optionally create) a User from verified token claims.
     *
     * Returns null when:
     * - the configured resolve key is missing or empty in the claims,
     * - the claims describe an anonymous user and `allow_anonymous` is off, or
     * - auto-creation is disabled and no matching user exists.
     */
    public function resolveByClaims(array $claims): ?object
    {
        $resolveBy = $this->getFirebaseResolveBy();

        if (is_string($resolveBy)) {
            $claimKey = $resolveBy;
        } else {
            $claimKey = array_key_first($resolveBy);
        }

        $claimValue = $this->getClaimValue($claims, $claimKey);

        if ($claimValue === null || $claimValue === '') {
            return null;
        }

        if ($this->isAnonymousClaim($claims) && ! config('firebase-authentication.allow_anonymous', false)) {
            return null;
        }

        $id = (string) $claimValue;
        $attributes = $this->transformClaims($claims);

        $user = $this->updateOrCreateUser($id, $attributes, $claims);

        if ($user === null) {
            return null;
        }

        $user->setClaims($claims);

        event(new FirebaseUserResolved($user, $claims));

        return $user;
    }

    /**
     * Update or create user.
     *
     * @param  int|string  $id
     */
    public function updateOrCreateUser($id, array $attributes, array $claims = []): ?object
    {
        $resolveBy = $this->getFirebaseResolveBy();
        $modelAttribute = is_string($resolveBy) ? $resolveBy : array_values($resolveBy)[0];

        if ($user = $this->where($modelAttribute, $id)->first()) {
            $user->fill($attributes);
            $this->syncEmailVerification($user, $claims);

            if ($user->isDirty()) {
                $user->save();
                event(new FirebaseUserUpdated($user, $claims));
            }

            return $user;
        }

        if (! config('firebase-authentication.auto_create_users', true)) {
            return null;
        }

        $user = $this->fill($attributes);
        $user->$modelAttribute = $id;
        $this->syncEmailVerification($user, $claims);
        $user->save();

        event(new FirebaseUserCreated($user, $claims));

        return $user;
    }

    /**
     * Apply Firebase's `email_verified` claim to the user model.
     *
     * Sets the configured timestamp column (default `email_verified_at`)
     * to now() when the claim is truthy and the column is currently empty.
     * Existing verification timestamps are never overwritten.
     */
    protected function syncEmailVerification(object $user, array $claims): void
    {
        if (empty($claims)) {
            return;
        }

        if (! config('firebase-authentication.email_verification.enabled', true)) {
            return;
        }

        if (empty($claims['email_verified'])) {
            return;
        }

        $column = config('firebase-authentication.email_verification.column', 'email_verified_at');

        if (! $this->modelHasAttribute($user, $column)) {
            return;
        }

        if (! empty($user->{$column})) {
            return;
        }

        $user->{$column} = $user->freshTimestamp();
    }

    /**
     * Whether the given attribute is exposed by the user model.
     *
     * Matches against runtime attributes, $fillable, and $casts (Laravel's
     * default User stub declares `email_verified_at` via $casts only).
     * Models holding the column but declaring it nowhere should add it to
     * $fillable or $casts to opt in.
     */
    protected function modelHasAttribute(object $user, string $attribute): bool
    {
        if (array_key_exists($attribute, $user->getAttributes())) {
            return true;
        }

        if (in_array($attribute, $user->getFillable(), true)) {
            return true;
        }

        if (array_key_exists($attribute, $user->getCasts())) {
            return true;
        }

        return false;
    }

    /**
     * Transform claims to attributes.
     *
     * Claim keys may use dot notation to read nested values
     * (e.g. `'organization_id' => 'organization.id'`).
     */
    public function transformClaims(array $claims): array
    {
        $attributes = [];

        foreach ($this->getFirebaseClaimsMapping() as $attribute => $claimKey) {
            $value = $this->getClaimValue($claims, $claimKey);

            if ($value === null || $value === '' || ! is_scalar($value)) {
                continue;
            }

            $attributes[$attribute] = (string) $value;
        }

        return $attributes;
    }

    /**
     * Read a claim value, supporting dot notation for nested claims.
     *
     * `'organization.id'` resolves to `$claims['organization']['id']`.
     * A literal top-level key wins over the dotted path if both exist —
     * Firebase custom claims occasionally include literal `.` in keys.
     */
    protected function getClaimValue(array $claims, string $claimKey): mixed
    {
        if (array_key_exists($claimKey, $claims)) {
            return $claims[$claimKey];
        }

        if (! str_contains($claimKey, '.')) {
            return null;
        }

        return data_get($claims, $claimKey);
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
        return $this->isAnonymousClaim($this->getClaims());
    }

    /**
     * Whether the given claims describe a Firebase anonymous sign-in.
     */
    protected function isAnonymousClaim(array $claims): bool
    {
        return ($claims['firebase']['sign_in_provider'] ?? null) === 'anonymous';
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
        return '';
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        return null;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        // no-op: Firebase auth doesn't use remember tokens
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string|null
     */
    public function getRememberTokenName()
    {
        return null;
    }
}
