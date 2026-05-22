<?php

namespace Firevel\FirebaseAuthentication;

use Firevel\FirebaseAuthentication\Events\FirebaseUserResolved;
use Illuminate\Foundation\Auth\User as Authenticatable;

class FirebaseIdentity extends Authenticatable
{
    use FirebaseAuthenticable;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * No database is used in microservice mode, so the Firebase UID is the identifier.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Resolve the Firebase UID directly into the `id` attribute.
     *
     * Microservices have no users table, so $identity->id holds the Firebase UID
     * for use as the auth identifier.
     *
     * @var array|string
     */
    protected $firebaseResolveBy = ['sub' => 'id'];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Save blocker.
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        return true;
    }

    /**
     * Resolve a stateless identity from verified token claims.
     *
     * Microservice mode has no database, so this never returns null —
     * the `?object` return type is kept only for signature parity with
     * the trait method it overrides.
     */
    public function resolveByClaims(array $claims): ?object
    {
        $resolveBy = $this->getFirebaseResolveBy();

        if (is_string($resolveBy)) {
            $claimKey = $resolveBy;
            $modelAttribute = $resolveBy;
        } else {
            $claimKey = array_key_first($resolveBy);
            $modelAttribute = array_values($resolveBy)[0];
        }

        $attributes = $this->transformClaims($claims);
        $attributes[$modelAttribute] = (string) $claims[$claimKey];

        $identity = $this->fill($attributes);
        $this->syncEmailVerification($identity, $claims);
        $identity->setClaims($claims);

        event(new FirebaseUserResolved($identity, $claims));

        return $identity;
    }

    /**
     * Stateless identities have no schema. With `$guarded = []` every claim
     * is fair game, so we always allow setting the verification column.
     */
    protected function modelHasAttribute(object $user, string $attribute): bool
    {
        return true;
    }
}
