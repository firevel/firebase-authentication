# Laravel Firebase Authentication

A robust Firebase Authentication API driver for Laravel and Firevel.

## Introduction

Laravel Firebase Authentication provides a sophisticated firebase guard for user authentication via the Firebase Authentication JWT token. This allows you to securely authenticate users in your Laravel or Firevel applications, leveraging the power of [Firebase Authentication](https://firebase.google.com/docs/auth/web/firebaseui).

## Getting Started

Follow these steps to get started with Laravel Firebase Authentication.

### Installation
Begin by installing the package using composer with the command:

```
composer require firevel/firebase-authentication
```

### Standard Configuration
1. **Update `config/auth.php`**: Specify Firebase as the authentication driver for your application.
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
2. **Set Firebase project name**: Configure your firebase project by adding **`GOOGLE_CLOUD_PROJECT`** to your environment variables or set the **`firebase.project_id`** config variable.
3. **Update your `User` model**: Integrate **`Firevel\FirebaseAuthentication\FirebaseAuthenticable`** trait, set **`$incrementing = false`** and define `$fillable`.

Below are examples of how to update your User model for Eloquent and Firequent:

Eloquent
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
Firequent
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
4. **Update Migration for users table**: If you're using Eloquent, you'll need to manually create or update the migration for the users table.
```
$table->string('id');
$table->string('name');
$table->string('email')->unique();
$table->string('picture');
$table->timestamps();
```

## Micro-service Configuration

To avoid sharing users database credentials between micro-services, the recommended configuration differs slightly:

1. **Update `config/auth.php`**: Specify Firebase as the authentication driver for the 'api' guard.
```
'guards' => [
    ...
    'api' => [
        'driver' => 'firebase',
        'provider' => 'users',
    ],
],
```
2. **Update User Provider**: In the **`config/auth.php`** file, define the user provider to use the **`Firevel\FirebaseAuthentication\FirebaseIdentity`** model.


## Web Guard Usage

To utilize firebase authentication within your web routes, it is necessary to attach the bearer token to each HTTP request.

The bearer token can be stored in the `bearer_token` cookie variable. To do this, add the following to your `Kernel.php`:

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

If the EncryptCookies middleware is in use, the following settings must be applied:
```
    protected $except = [
        ...
        'bearer_token',
        ...
    ];
```
