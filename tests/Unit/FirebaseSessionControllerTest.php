<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\Contracts\TokenVerifier;
use Firevel\FirebaseAuthentication\Http\Controllers\FirebaseSessionController;
use Firevel\FirebaseAuthentication\Tests\Fixtures\User;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;

class FirebaseSessionControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('firebase_id')->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('avatar_url')->nullable();
        });

        config()->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
    }

    /** @test */
    public function it_logs_user_in_with_a_valid_firebase_token()
    {
        $controller = $this->controllerThatVerifies('valid-jwt', [
            'sub' => 'firebase-uid-123',
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);

        $response = $controller->login($this->makeRequest('Bearer valid-jwt'));

        $this->assertEquals(200, $response->status());
        $this->assertEquals(['authenticated' => true], $response->getData(true));

        $user = Auth::guard('web')->user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('firebase-uid-123', $user->firebase_id);
        $this->assertEquals('user@example.com', $user->email);
    }

    /** @test */
    public function it_returns_401_when_token_is_missing()
    {
        $controller = $this->controllerThatRejects();

        $response = $controller->login($this->makeRequest(null));

        $this->assertEquals(401, $response->status());
        $this->assertEquals(['error' => 'Missing Firebase ID token.'], $response->getData(true));
        $this->assertNull(Auth::guard('web')->user());
    }

    /** @test */
    public function it_returns_401_when_token_is_invalid()
    {
        $controller = $this->controllerThatRejects();

        $response = $controller->login($this->makeRequest('Bearer bad-jwt'));

        $this->assertEquals(401, $response->status());
        $this->assertEquals(['error' => 'Invalid Firebase ID token.'], $response->getData(true));
        $this->assertNull(Auth::guard('web')->user());
    }

    /** @test */
    public function logout_clears_the_session()
    {
        $user = User::create([
            'firebase_id' => 'firebase-uid-999',
            'email' => 'logout@example.com',
            'name' => 'Logout User',
        ]);

        Auth::guard('web')->login($user);
        $this->assertNotNull(Auth::guard('web')->user());

        $controller = $this->controllerThatRejects();
        $response = $controller->logout($this->makeRequest(null));

        $this->assertEquals(200, $response->status());
        $this->assertEquals(['authenticated' => false], $response->getData(true));
        $this->assertNull(Auth::guard('web')->user());
    }

    /** @test */
    public function it_respects_a_custom_guard_via_config()
    {
        config()->set('firebase-authentication.session.guard', 'portal');
        config()->set('auth.guards.portal', [
            'driver' => 'session',
            'provider' => 'users',
        ]);

        $controller = $this->controllerThatVerifies('valid-jwt', [
            'sub' => 'firebase-uid-321',
            'email' => 'portal@example.com',
            'name' => 'Portal User',
        ]);

        $response = $controller->login($this->makeRequest('Bearer valid-jwt'));

        $this->assertEquals(200, $response->status());
        $this->assertNotNull(Auth::guard('portal')->user());
        $this->assertNull(Auth::guard('web')->user());
    }

    protected function makeRequest(?string $authorization): Request
    {
        $request = Request::create('/auth/firebase', 'POST');

        if ($authorization !== null) {
            $request->headers->set('Authorization', $authorization);
        }

        $request->setLaravelSession($this->app->make('session.store'));

        return $request;
    }

    protected function controllerThatVerifies(string $expectedToken, array $payload): FirebaseSessionController
    {
        $tokenStub = new class($payload)
        {
            public function __construct(private array $payload)
            {
            }

            public function payload(): array
            {
                return $this->payload;
            }
        };

        return new class(app(TokenVerifier::class), $expectedToken, $tokenStub) extends FirebaseSessionController
        {
            public function __construct(
                TokenVerifier $verifier,
                private string $expectedToken,
                private object $tokenStub,
            ) {
                parent::__construct($verifier);
            }

            protected function verifyToken(string $token)
            {
                if ($token !== $this->expectedToken) {
                    throw new IdTokenVerificationFailed('unexpected token');
                }

                return $this->tokenStub;
            }
        };
    }

    protected function controllerThatRejects(): FirebaseSessionController
    {
        return new class(app(TokenVerifier::class)) extends FirebaseSessionController
        {
            protected function verifyToken(string $token)
            {
                throw new IdTokenVerificationFailed('rejected');
            }
        };
    }
}
