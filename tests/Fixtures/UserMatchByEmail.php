<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

use Firevel\FirebaseAuthentication\FirebaseAuthenticable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserMatchByEmail extends Authenticatable
{
    use FirebaseAuthenticable;

    protected $table = 'users_by_email';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $firebaseResolveBy = 'email';

    protected $fillable = ['email', 'name', 'picture'];

    public $timestamps = false;
}
