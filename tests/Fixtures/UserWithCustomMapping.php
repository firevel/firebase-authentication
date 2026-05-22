<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

use Firevel\FirebaseAuthentication\FirebaseAuthenticable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserWithCustomMapping extends Authenticatable
{
    use FirebaseAuthenticable;

    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $firebaseClaimsMapping = [
        'email' => 'email',
        'name' => 'name',
        'phone' => 'phone_number',
        'locale' => 'locale',
    ];

    protected $fillable = ['id', 'email', 'name', 'phone', 'locale'];

    public $timestamps = false;
}
