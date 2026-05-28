<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

class UserWithEmailResolve extends User
{
    protected $table = 'users_by_email';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $firebaseResolveBy = 'email';

    protected $fillable = ['email', 'name'];
}
