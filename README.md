# Firebase Authentication for Laravel

Firebase authentication API driver for Laravel/Firevel.

## Overview

The driver contains a firebase guard that authenticates user by Firebase Authentication JWT token. To login use [Firebase Authentication](https://firebase.google.com/docs/auth/web/firebaseui).

## Installation

### Install the package using composer.
```
composer require firevel/firebase-authentication
```

### Update `config/auth.php`.
```
'guards' => [
    'web' => [
        'driver' => 'firebase',
        'provider' => 'users',
    ],

    'api' => [
        'driver' => 'firebase',
        'provider' => 'users',
    ],
],
```
### Set firebase project name.
Add `GOOGLE_CLOUD_PROJECT` to your env or `firebase.project_id` config variable.

### Update your `User` model.
Add `Firevel\FirebaseAuthentication\FirebaseAuthenticable` trait `$incrementing = false` and fillables.


Eloquent example:
```
<?php

namespace App;

use Firevel\FirebaseAuthentication\FirebaseAuthenticable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
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

### If you are using Eloquent you need to create or update migration for users table manually.
```
$table->string('id');
$table->string('name');
$table->string('email')->unique();
$table->string('picture');
$table->timestamps();
```

## Web guard

In order to use firebase authentication in web routes you must attach bearer token to each http request.

You can also store bearer token in `bearer_token` cookie variable and add to your `Kernel.php`:
```
    protected $middlewareGroups = [
        'web' => [
            ...
            \Firevel\FirebaseAuthentication\Http\Middleware\AddAccessTokenFromCookie::class,
            ...
        ],

        ...
    ];
```

If you are using `EncryptCookies` middleware you must set:

```
    protected $except = [
        ...
        'bearer_token',
        ...
    ];
```

## Usage

Attach to each API call regular bearer token provided by Firebase Authentication.
