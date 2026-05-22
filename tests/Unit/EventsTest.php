<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\Events\FirebaseUserCreated;
use Firevel\FirebaseAuthentication\Events\FirebaseUserResolved;
use Firevel\FirebaseAuthentication\Events\FirebaseUserUpdated;
use Firevel\FirebaseAuthentication\Tests\Fixtures\User;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class EventsTest extends TestCase
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
    public function it_fires_created_and_resolved_for_a_new_user()
    {
        Event::fake();

        (new User)->resolveByClaims([
            'sub' => 'new-1',
            'email' => 'new@example.com',
            'name' => 'New User',
        ]);

        Event::assertDispatched(FirebaseUserCreated::class);
        Event::assertDispatched(FirebaseUserResolved::class);
        Event::assertNotDispatched(FirebaseUserUpdated::class);
    }

    #[Test]
    public function it_fires_updated_and_resolved_when_existing_user_drifts()
    {
        User::create([
            'firebase_id' => 'existing-1',
            'email' => 'old@example.com',
            'name' => 'Old Name',
        ]);

        Event::fake();

        (new User)->resolveByClaims([
            'sub' => 'existing-1',
            'email' => 'new@example.com',
            'name' => 'New Name',
        ]);

        Event::assertDispatched(FirebaseUserUpdated::class);
        Event::assertDispatched(FirebaseUserResolved::class);
        Event::assertNotDispatched(FirebaseUserCreated::class);
    }

    #[Test]
    public function it_fires_only_resolved_when_user_is_unchanged()
    {
        User::create([
            'firebase_id' => 'stable-1',
            'email' => 'stable@example.com',
            'name' => 'Stable',
        ]);

        Event::fake();

        (new User)->resolveByClaims([
            'sub' => 'stable-1',
            'email' => 'stable@example.com',
            'name' => 'Stable',
        ]);

        Event::assertDispatched(FirebaseUserResolved::class);
        Event::assertNotDispatched(FirebaseUserCreated::class);
        Event::assertNotDispatched(FirebaseUserUpdated::class);
    }
}
