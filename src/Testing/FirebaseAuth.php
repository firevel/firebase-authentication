<?php

namespace Firevel\FirebaseAuthentication\Testing;

use Firevel\FirebaseAuthentication\Contracts\TokenVerifier;
use Illuminate\Contracts\Container\Container;

/**
 * Test helpers for the Firebase authentication package.
 *
 * Bind a fake verifier into the container so the package's guard accepts
 * any bearer token and resolves it to a user with the configured claims.
 *
 * Example:
 *
 *     FirebaseAuth::actingAs([
 *         'sub' => 'firebase-uid-1',
 *         'email' => 'tester@example.com',
 *         'email_verified' => true,
 *     ]);
 *
 *     $this->withHeader('Authorization', 'Bearer anything')
 *         ->getJson('/api/me')
 *         ->assertOk();
 */
class FirebaseAuth
{
    /**
     * Bind a fresh fake verifier into the container and return it.
     *
     * Calls beyond the first within a test re-use the existing binding so
     * `fake()` + `actingAs()` and `actingAs()` alone behave identically.
     */
    public static function fake(?Container $container = null): FakeTokenVerifier
    {
        $container ??= app();
        $existing = static::existing($container);

        if ($existing instanceof FakeTokenVerifier) {
            return $existing->reset();
        }

        $fake = new FakeTokenVerifier;
        $container->instance(TokenVerifier::class, $fake);

        return $fake;
    }

    /**
     * Authenticate subsequent requests as a user with the given claims.
     */
    public static function actingAs(array $claims, ?Container $container = null): FakeTokenVerifier
    {
        return static::fake($container)->setClaims($claims);
    }

    /**
     * Authenticate subsequent requests as a Firebase anonymous user.
     */
    public static function actingAsAnonymous(string $uid = 'fake-anonymous-uid', ?Container $container = null): FakeTokenVerifier
    {
        return static::actingAs([
            'sub' => $uid,
            'firebase' => ['sign_in_provider' => 'anonymous'],
        ], $container);
    }

    /**
     * Make the next verification attempt fail (e.g. expired/invalid token).
     */
    public static function rejectTokens(string $message = 'Invalid Firebase ID token (fake).', ?Container $container = null): FakeTokenVerifier
    {
        return static::fake($container)->failWith($message);
    }

    /**
     * Restore the real token verifier. Call from tearDown when sharing the
     * app between tests.
     */
    public static function forget(?Container $container = null): void
    {
        $container ??= app();
        $container->forgetInstance(TokenVerifier::class);
    }

    private static function existing(Container $container): ?TokenVerifier
    {
        return $container->bound(TokenVerifier::class)
            ? $container->make(TokenVerifier::class)
            : null;
    }
}
