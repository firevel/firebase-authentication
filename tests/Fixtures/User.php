<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

use Firevel\FirebaseAuthentication\FirebaseAuthenticatable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use FirebaseAuthenticatable;

    protected $table = 'users';

    protected $fillable = [
        'firebase_id',
        'name',
        'email',
        'avatar_url',
    ];

    protected $guarded = [];

    public $timestamps = false;
}
