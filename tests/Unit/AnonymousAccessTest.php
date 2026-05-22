<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\Tests\Fixtures\User;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class AnonymousAccessTest extends TestCase
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
    }

    #[Test]
    public function anonymous_claims_resolve_to_null_by_default()
    {
        $user = (new User)->resolveByClaims([
            'sub' => 'anon-uid',
            'firebase' => ['sign_in_provider' => 'anonymous'],
        ]);

        $this->assertNull($user);
        $this->assertEquals(0, User::count(), 'No row should be created for a rejected anonymous claim');
    }

    #[Test]
    public function anonymous_claims_are_accepted_when_allow_anonymous_is_enabled()
    {
        config(['firebase-authentication.allow_anonymous' => true]);

        $user = (new User)->resolveByClaims([
            'sub' => 'anon-uid',
            'firebase' => ['sign_in_provider' => 'anonymous'],
        ]);

        $this->assertNotNull($user);
        $this->assertEquals('anon-uid', $user->firebase_id);
        $this->assertTrue($user->isAnonymous());
    }

    #[Test]
    public function non_anonymous_claims_are_unaffected()
    {
        $user = (new User)->resolveByClaims([
            'sub' => 'google-uid',
            'email' => 'user@example.com',
            'firebase' => ['sign_in_provider' => 'google.com'],
        ]);

        $this->assertNotNull($user);
        $this->assertFalse($user->isAnonymous());
    }
}
