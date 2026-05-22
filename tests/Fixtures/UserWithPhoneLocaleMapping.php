<?php

namespace Firevel\FirebaseAuthentication\Tests\Fixtures;

class UserWithPhoneLocaleMapping extends User
{
    protected $firebaseClaimsMapping = [
        'email' => 'email',
        'name' => 'name',
        'phone' => 'phone_number',
        'locale' => 'locale',
    ];
}
