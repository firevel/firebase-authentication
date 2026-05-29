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
  - [Multiple Guards](#multiple-guards)
  - [Web Authentication](#web-authentication)
- [Configuration Reference](#configuration-reference)
- [Usage](#usage)
- [API Reference](#api-reference)
- [Common Use Cases](#common-use-cases)
- [Security Considerations](#security-considerations)
- [Troubleshooting](#troubleshooting)
- [Upgrading from v2.x](UPGRADING.md)
- [Contributing](#contributing)
- [License](#license)

## Features

- **JWT Token Verification**: Securely verify Firebase Authentication JWT tokens
- **Automatic User Sync**: Create or update users from Firebase claims, with an opt-out for invite-only flows
- **Email Verification Sync**: Firebase's `email_verified` claim populates `email_verified_at` automatically
- **Lifecycle Events**: `FirebaseUserCreated`, `FirebaseUserUpdated`, `FirebaseUserResolved` for plug-in hooks
- **Anonymous Authentication**: Built-in support for Firebase anonymous users
- **Microservice Ready**: Stateless authentication without database dependency
- **Web & API Guards**: Session-based exchange endpoint and bearer-token API auth
- **Configurable Caching**: Use Laravel's Redis/Memcached/database cache for the JWKS cache, not just the local filesystem
- **Clock-Skew Tolerance**: Optional leeway for token verification
- **Laravel Integration**: Native integration with Laravel's authentication system
- **Flexible User Models**: Works with Eloquent, or custom models

## Requirements

- PHP 8.2 or higher
- Laravel 11.x, 12.x, 13.x
- Firebase project with Authentication enabled

## Installation

Install the package via Composer:

```bash
composer require firevel/firebase-authentication
```

The package will automatically register its service provider.

## Upgrading from v2.x

See [UPGRADING.md](UPGRADING.md) for the v2 → v3 migration guide.

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
use Firevel\FirebaseAuthentication\FirebaseAuthenticatable;

class User extends Authenticatable
{
    use FirebaseAuthenticatable;

    protected $fillable = ['name', 'email', 'firebase_id', 'avatar_url'];

    // Optional: match users by email instead of Firebase UID (default: ['sub' => 'firebase_id'])
    // protected $firebaseResolveBy = 'email';

    // Optional: customize which Firebase claims map to which user attributes
    // (default: email→email, name→name, avatar_url→picture)
    // protected $firebaseClaimsMapping = [
    //     'email' => 'email',
    //     'name' => 'name',
    // ];
}
```

4. **Add the `firebase_id` and `avatar_url` columns** to your users table:
```bash
php artisan vendor:publish --tag=firebase-authentication-migrations
php artisan migrate
```

The published migration is additive: it adds `firebase_id` (unique, nullable) and `avatar_url` columns to your existing `users` table and makes `password` nullable. The existing `id` integer primary key is preserved.

5. **Protect your routes**:
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

Alternatively, publish the package config and set `project_id` there:

```bash
php artisan vendor:publish --tag=firebase-authentication-config
```

```php
// config/firebase-authentication.php
return [
    'project_id' => env('FIREBASE_PROJECT_ID', 'your-project-id'),
    // ...
];
```

> For backwards compatibility, the package also still reads `config('firebase.project_id')` and `env('GOOGLE_CLOUD_PROJECT')` if the package-namespaced value is unset.

#### 2. Update Authentication Configuration

Modify `config/auth.php` to use the Firebase driver for API auth:

```php
'guards' => [
    'api' => [
        'driver' => 'firebase',
        'provider' => 'users',
    ],
],
```

> For browser-based auth, see [Web Authentication](#web-authentication) below — the recommended path uses Laravel's `session` driver, not the `firebase` driver.

#### 3. Update Your User Model

Add the `FirebaseAuthenticatable` trait to your User model:

**Eloquent Example:**

```php
<?php

namespace App\Models;

use Firevel\FirebaseAuthentication\FirebaseAuthenticatable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, FirebaseAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'firebase_id',
        'avatar_url',
    ];

}
```

#### 4. Create/Update Users Table Migration

Two options, depending on whether you already have a `users` table.

**Option A — existing `users` table (e.g. Laravel default):** publish the bundled migration. It adds `firebase_id` (unique, nullable), `avatar_url`, and makes `password` nullable, leaving the rest of the table intact.

```bash
php artisan vendor:publish --tag=firebase-authentication-migrations
php artisan migrate
```

**Option B — creating a new `users` table from scratch:**

```bash
php artisan make:migration create_users_table
```

```php
public function up()
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('firebase_id')->unique()->nullable();
        $table->string('name')->nullable();
        $table->string('email')->unique()->nullable();
        $table->string('avatar_url')->nullable();
        $table->string('password')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });
}
```

```bash
php artisan migrate
```

> ℹ️ **Account linking & the `email` unique index.** Both options above put a **unique** index on `email`. This assumes your Firebase project uses the default **"Link accounts that use the same email"** setting under Authentication → Settings → User account linking — where one email maps to a single Firebase identity (and therefore a single user row). If instead you enable **"Create multiple accounts for each identity provider"**, Firebase can issue several identities (different `sub` values) that share the same email; with the unique index, the second sign-in fails on a duplicate-email error. To support that mode, **remove the `unique` constraint from the `email` column** in your users migration (users are still matched by `firebase_id`, so emails no longer need to be unique).

### Microservice Setup (without Database)

For microservices that only need to verify authentication without storing user data, use the `FirebaseIdentity` model.

`FirebaseIdentity` stores the Firebase UID on `$identity->firebase_id` — same shape as the `User` model — so `$request->user()->firebase_id` means the same thing across services. The model's `id` is intentionally unset by default; populate it from a custom claim only if you need integer-id parity with your core service (see [Exposing user_id / organization_id from custom claims](#exposing-user_id--organization_id-from-custom-claims)).

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

#### 3. Exposing `user_id` / `organization_id` from custom claims

In most microservice setups you just need to know "who is calling" — `$identity->firebase_id` is enough, plus `$identity->getClaims()` for anything else on the token.

When the core service mints Firebase custom claims (via the Admin SDK) carrying its own identifiers, you can expose them as attributes on the identity by subclassing `FirebaseIdentity` and customizing `$firebaseClaimsMapping`:

```php
namespace App\Auth;

use Firevel\FirebaseAuthentication\FirebaseIdentity;

class Identity extends FirebaseIdentity
{
    protected $firebaseClaimsMapping = [
        'id'              => 'user_id',         // integer id minted by the core service
        'organization_id' => 'organization.id', // nested claim via dot notation
        'email'           => 'email',
        'name'            => 'name',
    ];
}
```

Point the provider at your subclass instead of the package class:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Auth\Identity::class,
    ],
],
```

Now `$request->user()->id` is the integer assigned by your core service and `$request->user()->organization_id` reflects the nested claim. If a claim is missing the attribute is simply not set — there is no silent fallback to the Firebase UID, so misconfigurations stay visible. For this to work the core service must put the matching claims on the token via the Firebase Admin SDK (e.g. `auth.setCustomUserClaims($uid, ['user_id' => 42, 'organization' => ['id' => 7]])`); without that, the microservice has no way to know those values.

### Multiple Guards

You can configure multiple Firebase guards with different providers. This is useful when some routes need database-backed users while others only need token verification (e.g., a registration endpoint for users that don't exist in the database yet).

#### 1. Update Authentication Configuration

In `config/auth.php`, define two guards with different providers:

```php
'guards' => [
    'api' => [
        'driver' => 'firebase',
        'provider' => 'users',          // DB-backed User model
    ],
    'register' => [
        'driver' => 'firebase',
        'provider' => 'firebase',       // FirebaseIdentity (no DB)
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    'firebase' => [
        'driver' => 'eloquent',
        'model' => Firevel\FirebaseAuthentication\FirebaseIdentity::class,
    ],
],
```

#### 2. Protect Your Routes

Use the appropriate guard for each route:

```php
// Registration endpoint — user may not exist in DB yet
Route::middleware('auth:register')->post('/api/register', [RegisterController::class, 'store']);

// All other API routes — requires DB-backed user
Route::middleware('auth:api')->group(function () {
    Route::get('/api/profile', [ProfileController::class, 'show']);
    Route::put('/api/profile', [ProfileController::class, 'update']);
});
```

#### 3. Access User Data in Registration

In your registration controller, you can access the Firebase identity claims to create the database user:

```php
class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $identity = $request->user(); // FirebaseIdentity instance

        $user = User::create([
            'firebase_id' => $identity->firebase_id,
            'email' => $identity->email,
            'name' => $identity->name,
        ]);

        return response()->json($user, 201);
    }
}
```

Each guard resolves users through its own provider, so the `api` guard will look up/create users in the database while the `register` guard returns a lightweight `FirebaseIdentity` populated from JWT claims.

### Web Authentication

There are two ways to authenticate browser users — pick one based on whether your backend needs to hold the raw Firebase token.

| | **Option A — Laravel session** | **Option B — Cookie-carried bearer token** |
| --- | --- | --- |
| Backend has the Firebase token to forward | No | Yes |
| Login lifetime | Whatever `config('session.lifetime')` says | Whatever the cookie holds (refreshed by client) |
| CSRF & session ergonomics | Standard Laravel | Bypassed (cookie acts as a bearer) |
| Setup | Auto-registered `POST /auth/firebase` endpoint | Middleware in the `web` group |

#### Option A — Laravel session (recommended)

Exchange a Firebase ID token for a standard Laravel session, then let the session cookie drive web auth like any other Laravel app. The backend never stores the Firebase token; client-side `getIdToken()` keeps it fresh and re-presents it on demand.

**1. Configure a session-driven guard** in `config/auth.php`:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'firebase',
        'provider' => 'users',
    ],
],
```

**2. From the browser, POST a fresh Firebase ID token** to the auto-registered endpoint:

```javascript
const idToken = await firebase.auth().currentUser.getIdToken();

await fetch('/auth/firebase', {
    method: 'POST',
    credentials: 'include',
    headers: {
        Authorization: `Bearer ${idToken}`,
        'X-CSRF-TOKEN': csrfToken,            // when middleware = 'web'
        'X-Requested-With': 'XMLHttpRequest',
    },
});
```

After a 200 response, the browser holds a Laravel session cookie and every subsequent web request is authenticated normally. Logout is `DELETE /auth/firebase` (send the same `X-CSRF-TOKEN` header).

**3. Customize behavior** by publishing the config:

```bash
php artisan vendor:publish --tag=firebase-authentication-config
```

That writes `config/firebase-authentication.php`. The `session` block controls this flow:

```php
'session' => [
    'enabled'    => true,            // auto-register the routes
    'prefix'     => 'auth/firebase', // URL prefix
    'middleware' => 'web',           // 'web', 'api', or a custom group/array
    'guard'      => 'web',           // which session guard to log into
],
```

Set `firebase-authentication.session.enabled` to `false` if you'd rather wire up your own routes against `Firevel\FirebaseAuthentication\Http\Controllers\FirebaseSessionController`.

> Defaults apply even without publishing — you only need the file if you want to override.

**Tradeoff:** because the backend doesn't hold the Firebase token, it can't forward it to other Firebase-verifying services as the user. If you need that, send a fresh ID token in the `Authorization` header on the specific API calls that need it — the Firebase JS SDK guarantees freshness via `getIdToken()`. This pairs naturally with keeping the `api` guard above (`'driver' => 'firebase'`) for those calls.

#### Option B — Legacy: cookie-carried bearer token

Store the raw Firebase ID token in a cookie and have a middleware promote it to an `Authorization: Bearer …` header on every request. The backend can then read the token any time (useful for forwarding to other services), at the cost of bypassing Laravel's standard session/CSRF flow.

**1. Configure** the web guard to use the Firebase driver in `config/auth.php`:

```php
'guards' => [
    'web' => [
        'driver' => 'firebase',
        'provider' => 'users',
    ],
],
```

**2. Add the middleware *and* exclude the cookie from encryption** in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->encryptCookies(except: [
        'bearer_token', // must match firebase-authentication.token_cookie
    ]);

    $middleware->web(append: [
        \Firevel\FirebaseAuthentication\Http\Middleware\AddAccessTokenFromCookie::class,
    ]);
})
```

> ⚠️ **The encryption exclusion is required.** Laravel's `EncryptCookies` middleware runs before this one and silently nulls cookies it cannot decrypt — including a plain Firebase ID token your frontend just set. Without the `except: [...]` entry the middleware appears to do nothing and authentication fails silently. The cookie name must match `config('firebase-authentication.token_cookie')` (default `bearer_token`).

Your frontend is responsible for keeping the cookie up to date as Firebase ID tokens rotate.

## Configuration Reference

After publishing the config with `php artisan vendor:publish --tag=firebase-authentication-config`, the following options are available in `config/firebase-authentication.php`. All have sensible defaults — only override what you need.

| Key | Default | Purpose |
| --- | --- | --- |
| `project_id` | `env('GOOGLE_CLOUD_PROJECT')` | Firebase project ID used to verify token issuer/audience. |
| `token_cookie` | `null` (falls back to `bearer_token`) | Cookie name read by the legacy `AddAccessTokenFromCookie` middleware. |
| `leeway` | `null` | Seconds of clock skew tolerated when verifying tokens. Set to a small value like `30` if you see sporadic "token used before issued" errors. |
| `auto_create_users` | `true` | Set to `false` to reject verified tokens whose subject has no matching DB row (invite-only flows). `resolveByClaims()` returns `null` instead of creating. |
| `allow_anonymous` | `false` | Whether to accept Firebase anonymous sign-ins. Rejected by default; enable only if your app deliberately supports anonymous users. |
| `email_verification.enabled` | `true` | Sync the `email_verified` claim to a timestamp column on the user model. |
| `email_verification.column` | `email_verified_at` | Which column receives the timestamp. Only set if currently null — never overwrites an existing value. |
| `cache.store` | `null` | Laravel cache store name (e.g. `'redis'`). When set, the package shares the public-key cache via that store. When null, a local `FilesystemAdapter` is used. |
| `cache.path` | `null` | Custom filesystem cache location when `cache.store` is null. |
| `session.enabled` | `true` | Auto-register the `POST /auth/firebase` (login) and `DELETE /auth/firebase` (logout) routes. |
| `session.prefix` | `auth/firebase` | URL prefix for those routes. |
| `session.middleware` | `web` | Middleware group(s) for the session routes. |
| `session.guard` | `web` | Which session guard the controller logs into. |

### Events

The package dispatches three events during token resolution. Wire them up in your `EventServiceProvider`:

```php
use Firevel\FirebaseAuthentication\Events\FirebaseUserCreated;
use Firevel\FirebaseAuthentication\Events\FirebaseUserUpdated;
use Firevel\FirebaseAuthentication\Events\FirebaseUserResolved;

protected $listen = [
    FirebaseUserCreated::class => [SendWelcomeEmail::class, ProvisionTenant::class],
    FirebaseUserUpdated::class => [LogProfileSync::class],
    FirebaseUserResolved::class => [LogAuthenticatedRequest::class],
];
```

Each event exposes `$event->user` (the resolved model) and `$event->claims` (the decoded JWT payload).

- `FirebaseUserResolved` fires on every successful authentication, including unchanged users.
- `FirebaseUserCreated` fires only when a new row is inserted.
- `FirebaseUserUpdated` fires only when an existing row's attributes drift from the token claims.

### Using a Shared Cache Backend (Redis/Memcached)

By default the package writes Firebase's signing-key cache to the local filesystem. On ephemeral infrastructure (containers, serverless) this gives every fresh instance a cold cache. Point it at a shared Laravel cache store instead:

```php
// config/firebase-authentication.php
'cache' => [
    'store' => 'redis', // any store name from config/cache.php
],
```

The store must already be configured in `config/cache.php`. The package wraps it as a PSR-6 pool via Symfony's `Psr16Adapter`.

### Token Verification Leeway

If your server's clock can drift from Google's signing servers, set a small leeway:

```php
'leeway' => 30, // seconds
```

Both `FirebaseGuard` (API requests) and `FirebaseSessionController` (web exchange endpoint) honor this setting.

### Disabling Auto-Creation

For invite-only systems where unknown Firebase users must not become DB users:

```php
'auto_create_users' => false,
```

When set, `resolveByClaims()` returns `null` for tokens whose subject has no matching row. `FirebaseGuard` treats that as unauthenticated; `FirebaseSessionController` responds with `401 { "error": "No matching user account." }`.

### Testing

The package ships a fake token verifier so your application tests don't need real Firebase JWTs. `Firevel\FirebaseAuthentication\Testing\FirebaseAuth` swaps the contract binding in the container; subsequent requests through `FirebaseGuard` (or `FirebaseSessionController`) authenticate against the configured claims regardless of what bearer token is sent.

```php
use Firevel\FirebaseAuthentication\Testing\FirebaseAuth;

class ProfileTest extends TestCase
{
    public function test_authenticated_request(): void
    {
        FirebaseAuth::actingAs([
            'sub' => 'firebase-uid-1',
            'email' => 'tester@example.com',
            'email_verified' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer anything')
            ->getJson('/api/profile')
            ->assertOk();
    }

    public function test_anonymous_user(): void
    {
        FirebaseAuth::actingAsAnonymous();

        $this->withHeader('Authorization', 'Bearer anything')
            ->getJson('/api/posts')
            ->assertForbidden();
    }

    public function test_rejected_token(): void
    {
        FirebaseAuth::rejectTokens('Token expired');

        $this->withHeader('Authorization', 'Bearer anything')
            ->getJson('/api/profile')
            ->assertUnauthorized();
    }
}
```

Helpers:

- `FirebaseAuth::actingAs(array $claims)` — verify any token to the given claims.
- `FirebaseAuth::actingAsAnonymous(string $uid = '...')` — shortcut for the anonymous claim shape.
- `FirebaseAuth::rejectTokens(string $message = '...')` — make verification throw.
- `FirebaseAuth::fake()` — bind a fake verifier without configuring claims yet.
- `FirebaseAuth::forget()` — unbind the fake (call from `tearDown` if your tests share the app).

The fake implements `Firevel\FirebaseAuthentication\Contracts\TokenVerifier`, the same interface the real `KreaitTokenVerifier` adapter implements. The full guard + trait + event pipeline runs as in production — only the JWT cryptography is short-circuited.

### Email Verification Sync

Add a nullable `email_verified_at` timestamp column to your users table (Laravel's default `users` table already has one). Then a sign-in carrying `"email_verified": true` populates it automatically:

```php
$user = $request->user();
$user->email_verified_at; // Carbon\Carbon
$user->hasVerifiedEmail();  // true — when your User implements MustVerifyEmail
```

The column is set only when currently null, so manual `email_verified_at` updates from your app are never overwritten by a later token. To disable entirely, set `email_verification.enabled` to `false`.

`hasVerifiedEmail()` and the rest of Laravel's verification API only kick in if your `User` model implements the `Illuminate\Contracts\Auth\MustVerifyEmail` interface and uses the matching trait — that's standard Laravel, not something this package adds.

## Usage

### Basic Authentication

Standard Laravel — protect routes with `auth:api`, read the user with `auth()->user()` or `$request->user()`:

```php
Route::middleware('auth:api')->get('/profile', fn (Request $request) => $request->user());
```

### Anonymous Users

Firebase supports [anonymous authentication](https://firebase.google.com/docs/auth/web/anonymous-auth) — users that sign in without credentials.

> ⚠️ **Anonymous sign-in is rejected by default.** Anonymous tokens carry no email or name, can be issued unbounded, and are usually not what an authenticated route expects. Set `firebase-authentication.allow_anonymous` to `true` to accept them.

When enabled, you'll also typically need to:
1. Make `email` and `name` nullable on your `users` table — anonymous tokens carry neither.
2. Decide where to gate features per-route using `$user->isAnonymous()`:

```php
$user = auth()->user();

if ($user->isAnonymous()) {
    return response()->json(['error' => 'Anonymous users cannot create posts'], 403);
}

// Full-access user — proceed.
```

### Accessing JWT Claims

The full verified JWT payload is available on the user via `getClaims()` — useful for reading the sign-in provider, identities, or [custom claims](https://firebase.google.com/docs/auth/admin/custom-claims) set via the Firebase Admin SDK:

```php
$claims = auth()->user()->getClaims();

$provider = $claims['firebase']['sign_in_provider'] ?? null; // 'google.com', 'password', 'anonymous', ...
$role     = $claims['role'] ?? 'user';                       // custom claim, set via Admin SDK
```

### Working with Firebase Tokens

On API requests authenticated through the `firebase` driver, the raw bearer token is available on the resolved user:

```php
$user = auth()->user();
$token = $user->getFirebaseAuthenticationToken();
// Forward to other Firebase-verifying services, the Admin SDK, etc.
```

> **Session-mode caveat:** after `POST /auth/firebase` exchanges a token for a Laravel session, the backend no longer holds the Firebase token. On subsequent session-authenticated requests, `getFirebaseAuthenticationToken()` returns `null`. If you need a fresh token, have the client send it in the `Authorization` header for the specific call.

Token expiration is enforced automatically — verification fails on expired tokens and `auth()->user()` returns `null`.

## API Reference

### FirebaseAuthenticatable Trait

Methods available on User models using the `FirebaseAuthenticatable` trait:

> The legacy spelling `FirebaseAuthenticable` (no second `t`) still works in v3 as a deprecated alias — existing models that wrote `use FirebaseAuthenticable;` keep working unchanged. New code should prefer `FirebaseAuthenticatable`, which matches Laravel's `Authenticatable` contract.

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

#### `$firebaseResolveBy` Property

Controls which attribute is used to match existing users in your database. This determines how the package looks up users when authenticating.

```php
// Default: Match by Firebase UID (sub claim) to firebase_id column
protected $firebaseResolveBy = ['sub' => 'firebase_id'];

// Match by email (when claim name = model attribute)
protected $firebaseResolveBy = 'email';

// Match by Firebase UID to a different column
protected $firebaseResolveBy = ['sub' => 'firebase_uid'];
```

**Default behavior:** `['sub' => 'firebase_id']` — matches Firebase UID (sub claim) to the `firebase_id` column. The model's own `id` stays a normal Laravel integer primary key.

**Formats:**
- **Array format** `['claim_key' => 'model_attribute']` — Use when claim name differs from model attribute (e.g., `['sub' => 'firebase_uid']`)
- **String format** `'attribute_name'` — Use when claim and model attribute have the same name (e.g., `'email'`)

#### `$firebaseClaimsMapping` Property

Controls how Firebase JWT claims are mapped to user model attributes. Define this property in your User model to customize the mapping:

```php
protected $firebaseClaimsMapping = [
    'email' => 'email',                       // Model attribute => JWT claim key
    'name' => 'name',
    'avatar_url' => 'picture',                // Firebase's `picture` claim → `avatar_url` column
    'phone' => 'phone_number',                // Map phone_number claim to phone attribute
    'organization_id' => 'organization.id',   // Dot notation reads nested claims
];
```

**Default mapping:**
- `email` → `email`
- `name` → `name`
- `avatar_url` → `picture` (Firebase's `picture` claim is stored on the `avatar_url` attribute)

**Nested claims (dot notation):** claim keys may use `.` to drill into nested claim objects — e.g. `'organization_id' => 'organization.id'` resolves to `$claims['organization']['id']`. A literal top-level key always wins over the dotted path if both happen to exist on the token.

#### `transformClaims(array $claims): array`

Transforms JWT claims into user attributes using the `$firebaseClaimsMapping` property. Override this method for advanced customization beyond simple mapping:

```php
public function transformClaims(array $claims): array
{
    // Start with the standard mapping
    $attributes = parent::transformClaims($claims);

    // Add conditional logic or data transformation
    if (!empty($claims['email_verified'])) {
        $attributes['email_verified_at'] = $claims['email_verified']
            ? now()
            : null;
    }

    return $attributes;
}
```

### FirebaseGuard

The guard is automatically registered and handles authentication. You typically don't interact with it directly, but use Laravel's `auth()` helper.

## Common Use Cases

### Matching Users by Email Instead of Firebase UID

If you want to match Firebase users by email rather than by Firebase UID (e.g. you already have a user with that email and want to attach Firebase to it):

```php
// App/Models/User.php
class User extends Authenticatable
{
    use FirebaseAuthenticatable;

    // Match users by email instead of Firebase UID
    protected $firebaseResolveBy = 'email';

    protected $fillable = [
        'name',
        'email',
        'avatar_url',
    ];
}
```

The user model still uses Laravel's default integer `id` as the primary key. `email` just becomes the lookup key on each sign-in.

**Use case:** Migrating from a traditional auth system to Firebase while keeping existing user IDs and matching by email.

> ⚠️ **Heads-up:** anonymous Firebase users and phone-only sign-ins have no `email` claim. With `firebaseResolveBy = 'email'` they resolve to `null` (unauthenticated). If you need to support those flows alongside email matching, keep `firebaseResolveBy = ['sub' => 'firebase_id']` and write your own lookup-by-email logic where it matters.

### Using a Different Firebase UID Column Name

The default v3 column is `firebase_id`. If you'd rather call it something else (e.g. `firebase_uid` to match an existing convention), override `$firebaseResolveBy`:

```php
// App/Models/User.php
class User extends Authenticatable
{
    use FirebaseAuthenticatable;

    // Match Firebase UID (sub claim) to the firebase_uid column
    protected $firebaseResolveBy = ['sub' => 'firebase_uid'];

    protected $fillable = [
        'firebase_uid',
        'name',
        'email',
        'avatar_url',
    ];
}
```

**Migration:**
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('firebase_uid')->unique();
    $table->string('email')->unique()->nullable();
    $table->string('name')->nullable();
    $table->string('avatar_url')->nullable();
    $table->timestamps();
});
```

### Mapping additional claims to columns

For any extra Firebase claim you want stored on the user, add it to `$firebaseClaimsMapping` and `$fillable`:

```php
class User extends Authenticatable
{
    use FirebaseAuthenticatable;

    protected $firebaseClaimsMapping = [
        'email' => 'email',
        'name' => 'name',
        'avatar_url' => 'picture',
        'phone' => 'phone_number',
        'locale' => 'locale',
    ];

    protected $fillable = ['name', 'email', 'avatar_url', 'phone', 'locale'];
}
```

For conditional logic or data transformation, override `transformClaims()` — see the [API Reference](#transformclaimsarray-claims-array).

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

### Users not being created/updated

**Problem:** User model not syncing with Firebase claims.

**Solution:**
1. Verify `FirebaseAuthenticatable` trait is added to User model
2. Check `$fillable` includes: `['name', 'email', 'firebase_id', 'avatar_url']`
3. Verify the `firebase_id` column exists on the users table (run the bundled migration or add it manually)

### Web guard not working

**Problem:** Authentication works for API but not web routes.

**If using Option A (Laravel session):**
1. Verify `config('auth.guards.web.driver')` is `session`, not `firebase`
2. Confirm `POST /auth/firebase` returns 200 — the client must include the Firebase ID token in `Authorization: Bearer …`
3. If `firebase-authentication.session.middleware = 'web'`, the request must carry a valid CSRF token; switch to `'api'` for SPAs that handle CSRF separately
4. Make sure the browser is keeping cookies across requests (`credentials: 'include'` in fetch / `withCredentials` in axios)

**If using Option B (cookie-carried bearer):**
1. Ensure `AddAccessTokenFromCookie` middleware is added to the web middleware group
2. **Most common cause of silent failure:** make sure the cookie name is listed in `encryptCookies(except: [...])`. Without this, Laravel's `EncryptCookies` middleware nulls the cookie before our middleware reads it.
3. Check that the frontend is setting the cookie (default name: `bearer_token`)
4. Verify cookie domain matches your application domain

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

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
