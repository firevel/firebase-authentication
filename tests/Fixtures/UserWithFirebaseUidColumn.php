<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

class UserWithFirebaseUidColumn extends User
{
    protected $table = 'users_with_firebase_uid';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $firebaseResolveBy = ['sub' => 'firebase_uid'];

    protected $fillable = ['firebase_uid', 'email', 'name'];
}
