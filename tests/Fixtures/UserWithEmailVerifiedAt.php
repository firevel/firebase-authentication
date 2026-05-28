<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

use Firevel\FirebaseAuthentication\FirebaseAuthenticable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserWithEmailVerifiedAt extends Authenticatable
{
    use FirebaseAuthenticable;

    protected $table = 'users';

    protected $fillable = [
        'firebase_id',
        'name',
        'email',
        'avatar_url',
        'email_verified_at',
    ];

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
