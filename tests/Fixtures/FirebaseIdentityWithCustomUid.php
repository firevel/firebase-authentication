<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

use Firevel\FirebaseAuthentication\FirebaseIdentity;

class FirebaseIdentityWithCustomUid extends FirebaseIdentity
{
    protected $firebaseResolveBy = ['sub' => 'firebase_uid'];

    protected $fillable = ['firebase_uid', 'email', 'name'];
}
