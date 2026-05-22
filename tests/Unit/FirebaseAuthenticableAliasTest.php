<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\FirebaseAuthenticable;
use Firevel\FirebaseAuthentication\FirebaseAuthenticatable;
use Firevel\FirebaseAuthentication\Tests\Fixtures\UserWithEmailVerifiedAt;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression test for the v2 → v3 rename of `FirebaseAuthenticable` →
 * `FirebaseAuthenticatable`. The old name is kept as a deprecated alias
 * that composes the canonical trait, so existing user models that wrote
 * `use FirebaseAuthenticable;` keep working without changes.
 */
class FirebaseAuthenticableAliasTest extends TestCase
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

    #[Test]
    public function the_deprecated_alias_composes_the_canonical_trait()
    {
        // Models using the legacy `FirebaseAuthenticable` name pull in the
        // canonical `FirebaseAuthenticatable` trait transitively.
        $traits = class_uses_recursive(UserWithEmailVerifiedAt::class);

        $this->assertContains(FirebaseAuthenticable::class, $traits);
        $this->assertContains(FirebaseAuthenticatable::class, $traits);
    }

    #[Test]
    public function models_using_the_legacy_trait_still_resolve_claims()
    {
        $user = (new UserWithEmailVerifiedAt)->resolveByClaims([
            'sub' => 'legacy-trait-1',
            'email' => 'legacy@example.com',
            'name' => 'Legacy User',
        ]);

        $this->assertNotNull($user);
        $this->assertEquals('legacy-trait-1', $user->firebase_id);
        $this->assertEquals('legacy@example.com', $user->email);
    }
}
