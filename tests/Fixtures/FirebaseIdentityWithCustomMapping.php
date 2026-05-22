<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

use Firevel\FirebaseAuthentication\FirebaseIdentity;

class FirebaseIdentityWithCustomMapping extends FirebaseIdentity
{
    protected $firebaseClaimsMapping = [
        'email' => 'email',
        'name' => 'name',
        'phone' => 'phone_number',
    ];
}
