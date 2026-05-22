<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\Contracts\TokenVerifier;
use Firevel\FirebaseAuthentication\FirebaseGuard;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Illuminate\Http\Request;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class FirebaseGuardErrorHandlingTest extends TestCase
{
    #[Test]
    public function expired_tokens_return_null_in_production()
    {
        config(['app.debug' => false]);

        $guard = new FirebaseGuard(new ThrowingVerifier(
            new IdTokenVerificationFailed('The token is expired.')
        ));

        $this->assertNull($guard->user($this->bearerRequest('expired-token')));
    }

    #[Test]
    public function expired_tokens_return_null_in_debug_mode()
    {
        // Expired tokens are routine — even in debug we don't throw,
        // to avoid spam during local dev.
        config(['app.debug' => true]);

        $guard = new FirebaseGuard(new ThrowingVerifier(
            new IdTokenVerificationFailed('The token is expired.')
        ));

        $this->assertNull($guard->user($this->bearerRequest('expired-token')));
    }

    #[Test]
    public function other_verification_failures_return_null_in_production()
    {
        config(['app.debug' => false]);

        $guard = new FirebaseGuard(new ThrowingVerifier(
            new IdTokenVerificationFailed('The token has an invalid signature.')
        ));

        $this->assertNull($guard->user($this->bearerRequest('bad-token')));
    }

    #[Test]
    public function other_verification_failures_throw_in_debug_mode()
    {
        config(['app.debug' => true]);

        $guard = new FirebaseGuard(new ThrowingVerifier(
            new IdTokenVerificationFailed('The token has an invalid signature.')
        ));

        $this->expectException(IdTokenVerificationFailed::class);
        $guard->user($this->bearerRequest('bad-token'));
    }

    #[Test]
    public function unrelated_exceptions_bubble_even_in_production()
    {
        // Database errors, type errors, etc. must NOT be silently swallowed —
        // those are real bugs, not routine auth failures.
        config(['app.debug' => false]);

        $guard = new FirebaseGuard(new ThrowingVerifier(
            new RuntimeException('database connection lost')
        ));

        $this->expectException(RuntimeException::class);
        $guard->user($this->bearerRequest('any-token'));
    }

    private function bearerRequest(string $token): Request
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        return $request;
    }
}

class ThrowingVerifier implements TokenVerifier
{
    public function __construct(private \Throwable $error) {}

    public function verifyIdToken(string $token): Token
    {
        throw $this->error;
    }

    public function verifyIdTokenWithLeeway(string $token, int $leewayInSeconds): Token
    {
        throw $this->error;
    }
}
