<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\Contracts\TokenVerifier;
use Firevel\FirebaseAuthentication\Testing\FakeTokenVerifier;
use Firevel\FirebaseAuthentication\Testing\FirebaseAuth;
use Firevel\FirebaseAuthentication\Tests\Fixtures\User;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;

class FirebaseAuthFakeTest extends TestCase
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

        Route::middleware('auth:api')->get('/_test/me', function (Request $request) {
            return response()->json([
                'id' => $request->user()->id,
                'firebase_id' => $request->user()->firebase_id,
                'email' => $request->user()->email,
                'anonymous' => $request->user()->isAnonymous(),
            ]);
        });
    }

    protected function tearDown(): void
    {
        FirebaseAuth::forget();
        parent::tearDown();
    }

    /** @test */
    public function acting_as_authenticates_requests_with_the_given_claims()
    {
        FirebaseAuth::actingAs([
            'sub' => 'fake-uid-1',
            'email' => 'tester@example.com',
            'name' => 'Tester',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer anything')
            ->getJson('/_test/me');

        $response->assertOk()
            ->assertJsonFragment([
                'firebase_id' => 'fake-uid-1',
                'email' => 'tester@example.com',
                'anonymous' => false,
            ]);

        $this->assertDatabaseHas('users', ['firebase_id' => 'fake-uid-1']);
    }

    /** @test */
    public function acting_as_anonymous_marks_user_as_anonymous()
    {
        FirebaseAuth::actingAsAnonymous('anon-uid-1');

        $this->withHeader('Authorization', 'Bearer anything')
            ->getJson('/_test/me')
            ->assertOk()
            ->assertJsonFragment([
                'firebase_id' => 'anon-uid-1',
                'anonymous' => true,
            ]);
    }

    /** @test */
    public function reject_tokens_causes_auth_to_fail()
    {
        FirebaseAuth::rejectTokens();

        $this->withHeader('Authorization', 'Bearer anything')
            ->getJson('/_test/me')
            ->assertUnauthorized();
    }

    /** @test */
    public function fake_without_acting_as_still_returns_unauthenticated()
    {
        FirebaseAuth::fake();

        $this->withHeader('Authorization', 'Bearer anything')
            ->getJson('/_test/me')
            ->assertUnauthorized();
    }

    /** @test */
    public function repeated_acting_as_replaces_previous_claims()
    {
        FirebaseAuth::actingAs(['sub' => 'first-uid', 'email' => 'first@example.com']);
        FirebaseAuth::actingAs(['sub' => 'second-uid', 'email' => 'second@example.com']);

        $this->withHeader('Authorization', 'Bearer anything')
            ->getJson('/_test/me')
            ->assertOk()
            ->assertJsonFragment([
                'firebase_id' => 'second-uid',
                'email' => 'second@example.com',
            ]);
    }

    /** @test */
    public function forget_restores_the_original_verifier_binding()
    {
        FirebaseAuth::actingAs(['sub' => 'uid-1']);
        $this->assertInstanceOf(FakeTokenVerifier::class, app(TokenVerifier::class));

        FirebaseAuth::forget();

        $this->assertNotInstanceOf(FakeTokenVerifier::class, app(TokenVerifier::class));
    }

    /** @test */
    public function fake_verifier_throws_when_acting_as_was_not_called()
    {
        $verifier = FirebaseAuth::fake();

        $this->expectException(IdTokenVerificationFailed::class);
        $verifier->verifyIdToken('anything');
    }
}
