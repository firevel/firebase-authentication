<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\FirebaseGuard;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Illuminate\Http\Request;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\IdTokenVerifier;

class FirebaseGuardLeewayTest extends TestCase
{
    /** @test */
    public function it_uses_verify_id_token_when_leeway_is_unset()
    {
        $spy = new VerifierSpy;
        $guard = $this->guardWithVerifier($spy);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer jwt-token');
        $guard->user($request);

        $this->assertEquals(['verifyIdToken' => ['jwt-token']], $spy->calls);
    }

    /** @test */
    public function it_uses_verify_id_token_with_leeway_when_leeway_configured()
    {
        config(['firebase-authentication.leeway' => 30]);

        $spy = new VerifierSpy;
        $guard = $this->guardWithVerifier($spy);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer jwt-token');
        $guard->user($request);

        $this->assertEquals(['verifyIdTokenWithLeeway' => ['jwt-token', 30]], $spy->calls);
    }

    private function guardWithVerifier(object $verifier): FirebaseGuard
    {
        $guard = new FirebaseGuard(app(IdTokenVerifier::class));

        $reflection = new \ReflectionProperty($guard, 'verifier');
        $reflection->setAccessible(true);
        $reflection->setValue($guard, $verifier);

        return $guard;
    }
}

class VerifierSpy
{
    public array $calls = [];

    public function verifyIdToken(string $token)
    {
        $this->calls = ['verifyIdToken' => [$token]];
        throw new IdTokenVerificationFailed('stop');
    }

    public function verifyIdTokenWithLeeway(string $token, int $leeway)
    {
        $this->calls = ['verifyIdTokenWithLeeway' => [$token, $leeway]];
        throw new IdTokenVerificationFailed('stop');
    }
}
