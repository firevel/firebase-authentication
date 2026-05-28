<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

use Firevel\FirebaseAuthentication\FirebaseIdentity;

class FirebaseIdentityByEmail extends FirebaseIdentity
{
    protected $firebaseResolveBy = 'email';
}
