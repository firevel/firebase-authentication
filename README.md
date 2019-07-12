# Firebase Authentication for Laravel

Firebase authentication API driver for Laravel/Firevel.

## Overview

The driver contains a firebase guard that authenticates user by Firebase Authentication JWT token. To login use [Firebase Authentication](https://firebase.google.com/docs/auth/web/firebaseui).

## Installation

1) Install the package using composer:
```
composer require firevel/firebase-authentication
```

2) Update config/auth.php with:
```
'guards' => [
    ....
    'api' => [
        'driver' => 'firebase',
        'provider' => 'users',
    ],
],
```
3) Update your User model with `Firevel\FirebaseAuthentication\FirebaseAuthenticable` trait `$incrementing = false` and fillables.

Eloquent example:
```
<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'picture'
    ];
}

```
Firequent example:
```
<?php

namespace App;

use Firevel\FirebaseAuthentication\FirebaseAuthenticable;
use Firevel\Firequent\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Model implements Authenticatable
{
    use Notifiable, FirebaseAuthenticable;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'picture'
    ];

}

```

4. If you are using Eloquent you need to create or update migration for users table manually.
```
$table->string('id');
$table->string('name');
$table->string('email')->unique();
$table->string('picture');
$table->timestamps();
```

## Usage

Attach to each API call regular bearer token provided by Firebase Authentication.
