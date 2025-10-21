# Laravel Firebase Authentication

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Latest Stable Version](https://poser.pugx.org/firevel/firebase-authentication/v/stable)](https://packagist.org/packages/firevel/firebase-authentication)

A production-ready Firebase Authentication driver for Laravel that enables seamless JWT-based authentication using Firebase tokens.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
  - [Standard Setup (with Database)](#standard-setup-with-database)
  - [Microservice Setup (without Database)](#microservice-setup-without-database)
  - [Web Guard Configuration](#web-guard-configuration)
- [Usage](#usage)
  - [Basic Authentication](#basic-authentication)
  - [Anonymous Users](#anonymous-users)
  - [Accessing JWT Claims](#accessing-jwt-claims)
  - [Working with Firebase Tokens](#working-with-firebase-tokens)
- [API Reference](#api-reference)
- [Common Use Cases](#common-use-cases)
- [Security Considerations](#security-considerations)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

## Features

- **JWT Token Verification**: Securely verify Firebase Authentication JWT tokens
- **Automatic User Sync**: Automatically create/update users from Firebase claims
- **Anonymous Authentication**: Built-in support for Firebase anonymous users
- **Microservice Ready**: Stateless authentication without database dependency
- **Web & API Guards**: Support for both session-based and API authentication
- **Token Caching**: Optimized token verification with built-in caching
- **Laravel Integration**: Native integration with Laravel's authentication system
- **Flexible User Models**: Works with Eloquent, or custom models

## Requirements

- PHP 8.0 or higher
- Laravel 9.x, 10.x, 11.x, 12.x
- Firebase project with Authentication enabled

## Installation

Install the package via Composer:

```bash
composer require firevel/firebase-authentication
```

The package will automatically register its service provider.

## Quick Start

For a quick setup with API authentication:

1. **Set your Firebase project ID** in `.env`:
```env
GOOGLE_CLOUD_PROJECT=your-firebase-project-id
```

2. **Configure auth guard** in `config/auth.php`:
```php
'guards' => [
    'api' => [
        'driver' => 'firebase',
        'provider' => 'users',
    ],
],
```

3. **Add trait to your User model**:
```php
use Firevel\FirebaseAuthentication\FirebaseAuthenticable;

class User extends Authenticatable
{
    use FirebaseAuthenticable;

    public $incrementing = false;
    protected $fillable = ['name', 'email', 'picture'];
}
```

4. **Protect your routes**:
```php
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
```

That's it! Send requests with `Authorization: Bearer {firebase-jwt-token}` header.

## Configuration

### Standard Setup (with Database)

This setup stores user data in your database and syncs it with Firebase claims.

#### 1. Environment Configuration

Add your Firebase project ID to `.env`:

```env
GOOGLE_CLOUD_PROJECT=your-firebase-project-id
```

Alternatively, publish and configure the firebase config:

```php
// config/firebase.php
return [
    'project_id' => env('FIREBASE_PROJECT_ID', 'your-project-id'),
];
```

#### 2. Update Authentication Configuration

Modify `config/auth.php` to use the Firebase driver:

```php
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

#### 3. Update Your User Model

Add the `FirebaseAuthenticable` trait to your User model:

**Eloquent Example:**

```php
<?php

namespace App\Models;

use Firevel\FirebaseAuthentication\FirebaseAuthenticable;
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
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'picture',
    ];

}
```

#### 4. Create/Update Users Table Migration

If using a SQL database, create a migration for the users table:

```bash
php artisan make:migration create_users_table
```

```php
public function up()
{
    Schema::create('users', function (Blueprint $table) {
        $table->string('id')->primary(); // Firebase UID
        $table->string('name')->nullable();
        $table->string('email')->unique()->nullable();
        $table->string('picture')->nullable();
        $table->timestamps();
    });
}
```

Run the migration:

```bash
php artisan migrate
```

### Microservice Setup (without Database)

For microservices that only need to verify authentication without storing user data, use the `FirebaseIdentity` model.

#### 1. Update Authentication Configuration

In `config/auth.php`, configure only the API guard:

```php
'guards' => [
    'api' => [
        'driver' => 'firebase',
        'provider' => 'users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => Firevel\FirebaseAuthentication\FirebaseIdentity::class,
    ],
],
```

**Laravel 11+ Alternative:**

Use the `AUTH_MODEL` environment variable:

```env
GOOGLE_CLOUD_PROJECT=your-firebase-project-id
AUTH_MODEL=Firevel\FirebaseAuthentication\FirebaseIdentity
```

#### 2. Protect Your Routes

```php
Route::middleware('auth:api')->group(function () {
    Route::get('/data', [DataController::class, 'index']);
    Route::post('/process', [ProcessController::class, 'handle']);
});
```

**Benefits:**
- No database connection required for authentication
- Lightweight and fast
- Perfect for serverless deployments
- User data available from JWT claims

### Web Guard Configuration

To use Firebase authentication with web routes (session-based), you need to extract the bearer token from cookies.

#### 1. Add Middleware

In `bootstrap/app.php` (Laravel 11+):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Firevel\FirebaseAuthentication\Http\Middleware\AddAccessTokenFromCookie::class,
    ]);
})
```

Or in `app/Http/Kernel.php` (Laravel 10 and below):

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \Firevel\FirebaseAuthentication\Http\Middleware\AddAccessTokenFromCookie::class,
    ],
];
```

#### 2. Exclude Cookie from Encryption

The `bearer_token` cookie must not be encrypted.

**Laravel 11+** in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->encryptCookies(except: [
        'bearer_token',
    ]);
})
```

**Laravel 10 and below** in `app/Http/Middleware/EncryptCookies.php`:

```php
protected $except = [
    'bearer_token',
];
```

## Usage

### Basic Authentication

**In Controllers:**

```php
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
```

**In Routes:**

```php
Route::middleware('auth:api')->get('/profile', function (Request $request) {
    return $request->user();
});
```

**Manual Authentication Check:**

```php
if (auth()->check()) {
    $userId = auth()->id();
    $user = auth()->user();
}
```

### Anonymous Users

Firebase supports [anonymous authentication](https://firebase.google.com/docs/auth/web/anonymous-auth), allowing users to access your app without signing up.

**Check if User is Anonymous:**

```php
$user = auth()->user();

if ($user->isAnonymous()) {
    return response()->json([
        'message' => 'Limited features available. Sign up for full access!',
        'features' => ['read-only'],
    ]);
}

// Regular authenticated user
return response()->json([
    'message' => 'Welcome back!',
    'features' => ['read', 'write', 'share'],
]);
```

**Conditional Logic Based on Authentication Type:**

```php
class PostController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->isAnonymous()) {
            return response()->json([
                'error' => 'Anonymous users cannot create posts',
            ], 403);
        }

        // Create post for authenticated user
        $post = Post::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return response()->json($post, 201);
    }
}
```

**Frontend Example:**

```javascript
// Sign in anonymously
const userCredential = await firebase.auth().signInAnonymously();
const token = await userCredential.user.getIdToken();

// Make API request
const response = await fetch('/api/posts', {
    headers: {
        'Authorization': `Bearer ${token}`,
    },
});
```

### Accessing JWT Claims

All JWT token claims are available through the user model:

```php
$user = auth()->user();

// Get all claims
$claims = $user->getClaims();

// Access specific claim data
$firebase = $claims['firebase'] ?? [];
$signInProvider = $firebase['sign_in_provider'] ?? null; // 'google.com', 'password', 'anonymous', etc.
$identities = $firebase['identities'] ?? [];

// Check authentication method
if ($signInProvider === 'google.com') {
    // User signed in with Google
} elseif ($signInProvider === 'password') {
    // User signed in with email/password
}

// Access custom claims (set in Firebase Admin SDK)
$customClaims = $claims['custom_claim_name'] ?? null;
```

**Example with Custom Claims:**

```php
// Assuming you set custom claims in Firebase:
// admin.auth().setCustomUserClaims(uid, { role: 'admin', subscriptionTier: 'premium' })

$user = auth()->user();
$claims = $user->getClaims();

$role = $claims['role'] ?? 'user';
$tier = $claims['subscriptionTier'] ?? 'free';

if ($role === 'admin') {
    // Grant admin access
}

if ($tier === 'premium') {
    // Enable premium features
}
```

### Working with Firebase Tokens

**Get the Raw JWT Token:**

```php
$user = auth()->user();
$token = $user->getFirebaseAuthenticationToken();

// Use token for Firebase Admin SDK operations
// or pass to frontend for Firebase Realtime Database/Firestore authentication
```

**Validate Token Expiration:**

The package automatically handles token expiration. Expired tokens return `null` for `auth()->user()`.

```php
$user = auth()->user();

if (!$user) {
    return response()->json(['error' => 'Unauthorized or token expired'], 401);
}
```

## API Reference

### FirebaseAuthenticable Trait

Methods available on User models using the `FirebaseAuthenticable` trait:

#### `resolveByClaims(array $claims): object`

Resolves or creates a user from JWT token claims.

```php
$user = (new User)->resolveByClaims($claims);
```

#### `setClaims(array $claims): self`

Stores JWT claims on the user instance.

```php
$user->setClaims($claims);
```

#### `getClaims(): array`

Retrieves all JWT token claims.

```php
$claims = $user->getClaims();
```

#### `isAnonymous(): bool`

Checks if the user authenticated anonymously.

```php
if ($user->isAnonymous()) {
    // Handle anonymous user
}
```

#### `setFirebaseAuthenticationToken(string $token): self`

Stores the raw JWT token.

```php
$user->setFirebaseAuthenticationToken($token);
```

#### `getFirebaseAuthenticationToken(): ?string`

Retrieves the raw JWT token.

```php
$token = $user->getFirebaseAuthenticationToken();
```

#### `transformClaims(array $claims): array`

Transforms JWT claims into user attributes. Override this method to customize claim mapping:

```php
public function transformClaims(array $claims): array
{
    $attributes = parent::transformClaims($claims);

    // Add custom claim transformations
    if (!empty($claims['phone_number'])) {
        $attributes['phone'] = $claims['phone_number'];
    }

    return $attributes;
}
```

### FirebaseGuard

The guard is automatically registered and handles authentication. You typically don't interact with it directly, but use Laravel's `auth()` helper.

## Common Use Cases

### Role-Based Access Control with Custom Claims

```php
// Middleware: app/Http/Middleware/RequireRole.php
class RequireRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $claims = $user->getClaims();
        $userRole = $claims['role'] ?? 'user';

        if ($userRole !== $role) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}

// Route usage
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'users']);
});
```

### Syncing Additional User Data

```php
// App/Models/User.php
public function transformClaims(array $claims): array
{
    $attributes = parent::transformClaims($claims);

    // Map additional Firebase claims
    if (!empty($claims['phone_number'])) {
        $attributes['phone'] = $claims['phone_number'];
    }

    if (!empty($claims['email_verified'])) {
        $attributes['email_verified_at'] = $claims['email_verified']
            ? now()
            : null;
    }

    // Map custom claims
    if (!empty($claims['locale'])) {
        $attributes['locale'] = $claims['locale'];
    }

    return $attributes;
}
```

### Multi-Tenancy with Firebase

```php
// Set tenant ID as custom claim in Firebase
// admin.auth().setCustomUserClaims(uid, { tenantId: 'tenant-123' })

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $claims = $user->getClaims();

        $tenantId = $claims['tenantId'] ?? null;

        if (!$tenantId) {
            return response()->json(['error' => 'No tenant assigned'], 403);
        }

        // Set tenant for current request
        app()->instance('current_tenant', $tenantId);

        return $next($request);
    }
}
```

## Security Considerations

### Token Verification

- All tokens are verified using Firebase's official JWT verification library
- Token signatures are validated against Firebase's public keys
- Token expiration is automatically enforced
- Tokens are cached to improve performance

### Best Practices

1. **Always use HTTPS** in production to prevent token interception
2. **Implement token refresh** on the frontend before expiration (tokens expire after 1 hour)
3. **Never log tokens** in production environments
4. **Use custom claims** for roles/permissions instead of storing in database
5. **Validate user input** even for authenticated requests
6. **Rate limit** authentication endpoints to prevent abuse

### CORS Configuration

When using API authentication, configure CORS properly:

```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### Environment Variables

Never commit sensitive credentials. Use `.env`:

```env
GOOGLE_CLOUD_PROJECT=your-project-id
APP_ENV=production
APP_DEBUG=false
```

## Troubleshooting

### "No users provider found"

**Problem:** Laravel can't find the users provider.

**Solution:** Ensure `config/auth.php` has the provider configured:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],
```

### "Token verification failed"

**Problem:** JWT token can't be verified.

**Common causes:**
- Wrong `GOOGLE_CLOUD_PROJECT` environment variable
- Token expired (tokens are valid for 1 hour)
- Token from wrong Firebase project
- System clock skew

**Solution:**
1. Verify your Firebase project ID matches the token issuer
2. Check token expiration on frontend and refresh if needed
3. Ensure server time is synchronized (NTP)

### "Class 'IdTokenVerifier' not found"

**Problem:** Missing dependencies.

**Solution:**

```bash
composer require kreait/firebase-tokens symfony/cache
```

### Users not being created/updated

**Problem:** User model not syncing with Firebase claims.

**Solution:**
1. Verify `FirebaseAuthenticable` trait is added to User model
2. Check `$fillable` includes: `['name', 'email', 'picture']`
3. Ensure `$incrementing = false` is set
4. Verify database migration has `id` as string column

### "No password support for Firebase Users"

**Problem:** Trying to use password-based authentication methods.

**Expected behavior:** Firebase JWT authentication doesn't use passwords. This is correct.

**Solution:** Use Firebase Authentication on the frontend to obtain JWT tokens.

### Web guard not working

**Problem:** Authentication works for API but not web routes.

**Solution:**
1. Ensure `AddAccessTokenFromCookie` middleware is added to web middleware group
2. Verify `bearer_token` is excluded from cookie encryption
3. Check that frontend is setting the cookie correctly
4. Verify cookie domain matches your application domain

### Anonymous users can't be identified

**Problem:** `isAnonymous()` returns false for anonymous users.

**Solution:** Ensure you're using the latest version of the package. The anonymous detection was added recently. Update with:

```bash
composer update firevel/firebase-authentication
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/firevel/firebase-authentication.git

# Install dependencies
composer install

# Run tests (when available)
composer test

# Format code
./vendor/bin/pint
```

### Guidelines

- Follow PSR-12 coding standards
- Add tests for new features
- Update documentation for API changes
- Keep backwards compatibility when possible

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

- **Issues:** [GitHub Issues](https://github.com/firevel/firebase-authentication/issues)
- **Documentation:** [Firebase Authentication Docs](https://firebase.google.com/docs/auth)
- **Laravel Docs:** [Authentication](https://laravel.com/docs/authentication)
