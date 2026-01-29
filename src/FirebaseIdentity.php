<?php

namespace Firevel\FirebaseAuthentication;

use Illuminate\Foundation\Auth\User as Authenticatable;

class FirebaseIdentity extends Authenticatable
{
    use FirebaseAuthenticable;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

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
     * Get User by claim.
     *
     * @return self
     */
    public function resolveByClaims(array $claims): object
    {
        $resolveBy = $this->getFirebaseResolveBy();

        // Parse firebaseResolveBy to get claim key and model attribute
        if (is_string($resolveBy)) {
            $claimKey = $resolveBy;
            $modelAttribute = $resolveBy;
        } else {
            $claimKey = array_key_first($resolveBy);
            $modelAttribute = array_values($resolveBy)[0];
        }

        $attributes = $this->transformClaims($claims);
        $attributes[$modelAttribute] = (string) $claims[$claimKey];

        return $this->fill($attributes)->setClaims($claims);
    }
}
