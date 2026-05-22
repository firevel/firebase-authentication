<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | Your Firebase project ID. Used to verify ID tokens against Firebase's
    | public keys. When unset here, the package falls back to the legacy
    | config('firebase.project_id') key and finally the GOOGLE_CLOUD_PROJECT
    | environment variable.
    |
    */

    'project_id' => env('GOOGLE_CLOUD_PROJECT'),

    /*
    |--------------------------------------------------------------------------
    | Legacy Cookie Authentication
    |--------------------------------------------------------------------------
    |
    | Cookie name read by the AddAccessTokenFromCookie middleware. When
    | unset, the package falls back to config('firebase.token_cookie') and
    | finally to 'bearer_token'.
    |
    */

    'token_cookie' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Verification Leeway
    |--------------------------------------------------------------------------
    |
    | Seconds of clock skew tolerated when verifying token timing claims
    | (iat, exp, auth_time). When null, strict verification is used.
    | Setting a small value (e.g. 30) avoids spurious rejections when the
    | server clock drifts from Google's signing servers.
    |
    */

    'leeway' => null,

    /*
    |--------------------------------------------------------------------------
    | Auto-Create Users
    |--------------------------------------------------------------------------
    |
    | When true (default), users matching a verified Firebase token are
    | created automatically if they do not yet exist in the database.
    | Set to false for invite-only flows where unknown tokens should be
    | rejected as unauthenticated.
    |
    */

    'auto_create_users' => true,

    /*
    |--------------------------------------------------------------------------
    | Allow Anonymous Sign-In
    |--------------------------------------------------------------------------
    |
    | Firebase supports anonymous authentication. By default, this package
    | rejects tokens whose `firebase.sign_in_provider` is `anonymous` —
    | anonymous identities have no email/name, can be created unbounded, and
    | are usually not what an authenticated route expects.
    |
    | Set this to true if your app deliberately supports anonymous users.
    | You'll likely also need to make `email`/`name` nullable on the users
    | table, since anonymous tokens carry neither claim.
    |
    */

    'allow_anonymous' => false,

    /*
    |--------------------------------------------------------------------------
    | Email Verification Sync
    |--------------------------------------------------------------------------
    |
    | When enabled, a truthy Firebase `email_verified` claim populates the
    | configured column on the user model (typically `email_verified_at`).
    | The column is only set if it is currently null; an existing
    | verification timestamp is never overwritten.
    |
    */

    'email_verification' => [
        'enabled' => true,
        'column' => 'email_verified_at',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Backend
    |--------------------------------------------------------------------------
    |
    | The package caches Firebase's public signing keys to avoid fetching
    | them on every request. By default a local filesystem cache is used.
    | Set `store` to the name of a Laravel cache store (e.g. 'redis',
    | 'memcached', 'database') to share the cache across instances.
    |
    */

    'cache' => [
        'store' => null,
        'path' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Session-Based Web Authentication
    |--------------------------------------------------------------------------
    |
    | Configuration for FirebaseSessionController. Browser clients POST a
    | Firebase ID token to {prefix}; the package verifies the token and logs
    | the user into the configured Laravel session guard.
    |
    */

    'session' => [

        // Auto-register POST {prefix} (login) and POST {prefix}/logout routes.
        // Disable if you prefer to wire the controller into your own routes.
        'enabled' => true,

        // URL prefix for the login + logout endpoints.
        'prefix' => 'auth/firebase',

        // Middleware group(s) applied to the routes. 'web' enables CSRF and
        // session start; 'api' is friendlier for SPAs that handle CSRF on
        // their own. Accepts a string or an array.
        'middleware' => 'web',

        // The Laravel guard the controller logs into. Must use the 'session'
        // driver (or any guard that implements StatefulGuard).
        'guard' => 'web',

    ],

];
