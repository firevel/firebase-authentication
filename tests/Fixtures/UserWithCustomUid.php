<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

use Firevel\FirebaseAuthentication\FirebaseAuthenticable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserWithCustomUid extends Authenticatable
{
    use FirebaseAuthenticable;

    protected $table = 'users_custom_uid';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $firebaseResolveBy = ['sub' => 'firebase_uid'];

    protected $fillable = ['firebase_uid', 'email', 'name'];

    public $timestamps = false;
}
