<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\Tests\Fixtures\User;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class AutoCreateUsersTest extends TestCase
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
    public function it_returns_null_when_auto_create_disabled_and_no_existing_user()
    {
        config(['firebase-authentication.auto_create_users' => false]);

        $user = (new User)->resolveByClaims([
            'sub' => 'unknown-1',
            'email' => 'unknown@example.com',
        ]);

        $this->assertNull($user);
        $this->assertEquals(0, User::count());
    }

    #[Test]
    public function it_still_returns_existing_users_when_auto_create_disabled()
    {
        config(['firebase-authentication.auto_create_users' => false]);

        User::create([
            'firebase_id' => 'known-1',
            'email' => 'known@example.com',
            'name' => 'Known',
        ]);

        $user = (new User)->resolveByClaims([
            'sub' => 'known-1',
            'email' => 'known@example.com',
            'name' => 'Known',
        ]);

        $this->assertNotNull($user);
        $this->assertEquals('known@example.com', $user->email);
    }

    #[Test]
    public function it_creates_users_by_default()
    {
        $user = (new User)->resolveByClaims([
            'sub' => 'auto-1',
            'email' => 'auto@example.com',
        ]);

        $this->assertNotNull($user);
        $this->assertEquals('auto-1', $user->firebase_id);
        $this->assertEquals(1, User::count());
    }
}
