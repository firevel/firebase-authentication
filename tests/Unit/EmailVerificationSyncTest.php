<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\Tests\Fixtures\UserWithEmailVerifiedAt;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class EmailVerificationSyncTest extends TestCase
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
            $table->timestamp('email_verified_at')->nullable();
        });
    }

    /** @test */
    public function it_sets_email_verified_at_when_claim_is_true_and_column_is_null()
    {
        Carbon::setTestNow('2026-05-22 12:00:00');

        $user = (new UserWithEmailVerifiedAt)->resolveByClaims([
            'sub' => 'verified-1',
            'email' => 'verified@example.com',
            'email_verified' => true,
        ]);

        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->email_verified_at->equalTo('2026-05-22 12:00:00'));
    }

    /** @test */
    public function it_does_not_set_email_verified_at_when_claim_is_false()
    {
        $user = (new UserWithEmailVerifiedAt)->resolveByClaims([
            'sub' => 'unverified-1',
            'email' => 'unverified@example.com',
            'email_verified' => false,
        ]);

        $this->assertNull($user->email_verified_at);
    }

    /** @test */
    public function it_does_not_overwrite_existing_email_verified_at()
    {
        $existing = UserWithEmailVerifiedAt::create([
            'firebase_id' => 'preserve-1',
            'email' => 'preserve@example.com',
            'email_verified_at' => '2025-01-01 00:00:00',
        ]);

        Carbon::setTestNow('2026-05-22 12:00:00');

        $user = (new UserWithEmailVerifiedAt)->resolveByClaims([
            'sub' => 'preserve-1',
            'email' => 'preserve@example.com',
            'email_verified' => true,
        ]);

        $this->assertTrue($user->email_verified_at->equalTo('2025-01-01 00:00:00'));
        $this->assertEquals($existing->id, $user->id);
    }

    /** @test */
    public function it_can_be_disabled_via_config()
    {
        config(['firebase-authentication.email_verification.enabled' => false]);

        $user = (new UserWithEmailVerifiedAt)->resolveByClaims([
            'sub' => 'disabled-1',
            'email' => 'disabled@example.com',
            'email_verified' => true,
        ]);

        $this->assertNull($user->email_verified_at);
    }
}
