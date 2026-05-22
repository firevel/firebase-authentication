<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

use Firevel\FirebaseAuthentication\FirebaseIdentity;

class FirebaseIdentityWithNestedClaims extends FirebaseIdentity
{
    protected $firebaseClaimsMapping = [
        'email' => 'email',
        'organization_id' => 'organization.id',
        'organization_name' => 'organization.name',
    ];
}
