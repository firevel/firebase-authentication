<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

class UserWithClaimFilters extends User
{
    protected $firebaseClaimsMapping = [
        'email' => 'email',
        'name' => 'name',
        'avatar_url' => 'picture',
        'age' => 'age',
        'is_admin' => 'admin',
        'roles' => 'roles',
        'phone' => 'phone_number',
    ];

    // Keyed by the token claim, not the model attribute.
    protected $firebaseClaimFilters = [
        'picture' => 'url',
        'age' => 'integer',
        'admin' => 'boolean',
        'roles' => 'array',
        'phone_number' => 'string',
        // Closure filter assigned in the constructor (see below).
        'name' => null,
    ];

    public function __construct(array $attributes = [])
    {
        // Assign the closure filter at runtime (can't be a property default).
        $this->firebaseClaimFilters['name'] = fn ($value) => trim((string) $value) ?: null;

        parent::__construct($attributes);
    }
}
