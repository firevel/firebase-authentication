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
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        return true;
    }

    /**
     * Get User by claim.
     *
     * @param  array  $claims
     * @return self
     */
    public function resolveByClaims(array $claims): object
    {
        $attributes = $this->transformClaims($claims);
        $attributes['id'] = (string) $claims['sub'];

        return $this->fill($attributes);
    }
}
