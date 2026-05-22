<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

use Firevel\FirebaseAuthentication\FirebaseIdentity;

class FirebaseIdentityWithUserIdClaim extends FirebaseIdentity
{
    protected $firebaseClaimsMapping = [
        'id' => 'user_id',
        'email' => 'email',
        'name' => 'name',
    ];
}
