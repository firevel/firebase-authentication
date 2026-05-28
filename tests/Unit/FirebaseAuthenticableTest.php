<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\Tests\Fixtures\User;
use Firevel\FirebaseAuthentication\Tests\Fixtures\UserWithEmailResolve;
use Firevel\FirebaseAuthentication\Tests\Fixtures\UserWithFirebaseUidColumn;
use Firevel\FirebaseAuthentication\Tests\Fixtures\UserWithPhoneLocaleMapping;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class FirebaseAuthenticableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create users table
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('firebase_id')->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('avatar_url')->nullable();
        });
    }

    #[Test]
    public function it_returns_null_when_the_resolve_claim_is_missing()
    {
        $user = (new User)->resolveByClaims([
            // sub claim deliberately omitted
            'email' => 'noresolvekey@example.com',
        ]);

        $this->assertNull($user);
        $this->assertEquals(0, User::count());
    }

    #[Test]
    public function it_returns_null_when_the_resolve_claim_is_empty_string()
    {
        $user = (new User)->resolveByClaims([
            'sub' => '',
            'email' => 'empty-sub@example.com',
        ]);

        $this->assertNull($user);
        $this->assertEquals(0, User::count());
    }

    #[Test]
    public function it_resolves_user_by_claims_with_default_resolve_by()
    {
        $claims = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'name' => 'Test User',
            'picture' => 'https://example.com/photo.jpg',
        ];

        $user = (new User)->resolveByClaims($claims);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('user-123', $user->firebase_id);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('https://example.com/photo.jpg', $user->avatar_url);
        $this->assertEquals($claims, $user->getClaims());
    }

    #[Test]
    public function it_resolves_user_by_email_when_configured()
    {
        Schema::create('users_by_email', function ($table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
        });

        $userClass = new UserWithEmailResolve;

        $claims = [
            'sub' => 'firebase-uid-456',
            'email' => 'resolve@example.com',
            'name' => 'Resolved User',
        ];

        $user = $userClass->resolveByClaims($claims);

        $this->assertEquals('resolve@example.com', $user->email);
        $this->assertEquals('Resolved User', $user->name);
    }

    #[Test]
    public function it_resolves_user_by_custom_firebase_uid_column()
    {
        Schema::create('users_with_firebase_uid', function ($table) {
            $table->id();
            $table->string('firebase_uid')->unique();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
        });

        $userClass = new UserWithFirebaseUidColumn;

        $claims = [
            'sub' => 'custom-uid-789',
            'email' => 'custom@example.com',
            'name' => 'Custom User',
        ];

        $user = $userClass->resolveByClaims($claims);

        $this->assertEquals('custom-uid-789', $user->firebase_uid);
        $this->assertEquals('custom@example.com', $user->email);
    }

    #[Test]
    public function it_sets_and_gets_claims()
    {
        $user = new User;
        $claims = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'custom_claim' => 'custom_value',
        ];

        $user->setClaims($claims);

        $this->assertEquals($claims, $user->getClaims());
    }

    #[Test]
    public function it_returns_empty_array_when_no_claims_set()
    {
        $user = new User;

        $this->assertEquals([], $user->getClaims());
    }

    #[Test]
    public function it_detects_anonymous_users()
    {
        $user = new User;
        $claims = [
            'sub' => 'anonymous-user-123',
            'firebase' => [
                'sign_in_provider' => 'anonymous',
            ],
        ];

        $user->setClaims($claims);

        $this->assertTrue($user->isAnonymous());
    }

    #[Test]
    public function it_detects_non_anonymous_users()
    {
        $user = new User;
        $claims = [
            'sub' => 'google-user-123',
            'email' => 'user@example.com',
            'firebase' => [
                'sign_in_provider' => 'google.com',
            ],
        ];

        $user->setClaims($claims);

        $this->assertFalse($user->isAnonymous());
    }

    #[Test]
    public function it_returns_false_for_anonymous_check_when_no_firebase_claim()
    {
        $user = new User;
        $claims = [
            'sub' => 'user-123',
            'email' => 'user@example.com',
        ];

        $user->setClaims($claims);

        $this->assertFalse($user->isAnonymous());
    }

    #[Test]
    public function it_sets_and_gets_firebase_authentication_token()
    {
        $user = new User;
        $token = 'test-jwt-token-12345';

        $user->setFirebaseAuthenticationToken($token);

        $this->assertEquals($token, $user->getFirebaseAuthenticationToken());
    }

    #[Test]
    public function it_transforms_claims_to_attributes()
    {
        $user = new User;
        $claims = [
            'sub' => 'user-123',
            'email' => 'transform@example.com',
            'name' => 'Transform User',
            'picture' => 'https://example.com/pic.jpg',
            'other_claim' => 'ignored',
        ];

        $attributes = $user->transformClaims($claims);

        $this->assertEquals([
            'email' => 'transform@example.com',
            'name' => 'Transform User',
            'avatar_url' => 'https://example.com/pic.jpg',
        ], $attributes);
    }

    #[Test]
    public function it_transforms_claims_with_custom_mapping()
    {
        $userClass = new UserWithPhoneLocaleMapping;

        $claims = [
            'sub' => 'user-123',
            'email' => 'custom@example.com',
            'name' => 'Custom Mapping User',
            'phone_number' => '+1234567890',
            'locale' => 'en-US',
        ];

        $attributes = $userClass->transformClaims($claims);

        $this->assertEquals([
            'email' => 'custom@example.com',
            'name' => 'Custom Mapping User',
            'phone' => '+1234567890',
            'locale' => 'en-US',
        ], $attributes);
    }

    #[Test]
    public function it_skips_empty_claims_in_transformation()
    {
        $user = new User;
        $claims = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'name' => '',
            'picture' => null,
        ];

        $attributes = $user->transformClaims($claims);

        $this->assertEquals([
            'email' => 'test@example.com',
        ], $attributes);
    }

    #[Test]
    public function it_updates_existing_user_when_data_changes()
    {
        $user = User::create([
            'firebase_id' => 'update-test-123',
            'email' => 'old@example.com',
            'name' => 'Old Name',
        ]);

        $claims = [
            'sub' => 'update-test-123',
            'email' => 'new@example.com',
            'name' => 'New Name',
        ];

        $updatedUser = (new User)->resolveByClaims($claims);

        $this->assertEquals('update-test-123', $updatedUser->firebase_id);
        $this->assertEquals('new@example.com', $updatedUser->email);
        $this->assertEquals('New Name', $updatedUser->name);

        $this->assertDatabaseHas('users', [
            'firebase_id' => 'update-test-123',
            'email' => 'new@example.com',
            'name' => 'New Name',
        ]);
    }

    #[Test]
    public function it_does_not_save_when_user_data_is_unchanged()
    {
        $user = User::create([
            'firebase_id' => 'no-update-123',
            'email' => 'same@example.com',
            'name' => 'Same Name',
        ]);

        $claims = [
            'sub' => 'no-update-123',
            'email' => 'same@example.com',
            'name' => 'Same Name',
        ];

        // Mock to verify save is not called
        $resolvedUser = (new User)->resolveByClaims($claims);

        $this->assertEquals('no-update-123', $resolvedUser->firebase_id);
        $this->assertFalse($resolvedUser->isDirty());
    }

    #[Test]
    public function it_returns_empty_string_for_auth_password()
    {
        $user = new User;

        $this->assertSame('', $user->getAuthPassword());
    }

    #[Test]
    public function it_returns_null_for_remember_token()
    {
        $user = new User;

        $this->assertNull($user->getRememberToken());
    }

    #[Test]
    public function set_remember_token_is_a_no_op()
    {
        $user = new User;

        $this->assertNull($user->setRememberToken('some-token'));
        $this->assertNull($user->getRememberToken());
    }

    #[Test]
    public function it_returns_null_for_remember_token_name()
    {
        $user = new User;

        $this->assertNull($user->getRememberTokenName());
    }

    #[Test]
    public function it_returns_correct_auth_identifier_name()
    {
        $user = new User;

        $this->assertEquals('id', $user->getAuthIdentifierName());
    }

    #[Test]
    public function it_returns_correct_auth_identifier()
    {
        $user = User::create([
            'firebase_id' => 'identifier-test-123',
            'email' => 'identifier@example.com',
            'name' => 'Identifier Test',
        ]);

        $this->assertEquals($user->id, $user->getAuthIdentifier());
        $this->assertEquals('identifier-test-123', $user->firebase_id);
    }
}
