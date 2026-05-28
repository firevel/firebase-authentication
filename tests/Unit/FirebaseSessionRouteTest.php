<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\Testing\FirebaseAuth;
use Firevel\FirebaseAuthentication\Tests\Fixtures\User;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

/**
 * Exercises the auto-registered session routes through the HTTP layer so the
 * route SHAPE itself is covered — the controller unit tests call login()/logout()
 * directly and would pass regardless of how the routes are wired.
 *
 * The session is modelled as a resource: POST /auth/firebase creates it (login),
 * DELETE /auth/firebase destroys it (logout).
 */
class FirebaseSessionRouteTest extends TestCase
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

    #[Test]
    public function login_is_registered_as_post_on_the_bare_prefix()
    {
        $route = $this->app['router']->getRoutes()->getByName('firebase.session.store');

        $this->assertNotNull($route, 'Route firebase.session.store should be registered.');
        $this->assertSame('auth/firebase', $route->uri());
        $this->assertContains('POST', $route->methods());
    }

    #[Test]
    public function logout_is_registered_as_delete_on_the_bare_prefix()
    {
        $route = $this->app['router']->getRoutes()->getByName('firebase.session.destroy');

        $this->assertNotNull($route, 'Route firebase.session.destroy should be registered.');
        $this->assertSame('auth/firebase', $route->uri());
        $this->assertContains('DELETE', $route->methods());
    }

    #[Test]
    public function the_old_post_logout_subpath_is_no_longer_registered()
    {
        $this->postJson('/auth/firebase/logout')
            ->assertStatus(404);
    }

    #[Test]
    public function posting_a_valid_token_logs_the_user_in_and_creates_them()
    {
        FirebaseAuth::actingAs([
            'sub' => 'firebase-uid-route',
            'email' => 'route@example.com',
            'name' => 'Route User',
        ]);

        $this->withHeader('Authorization', 'Bearer anything')
            ->postJson('/auth/firebase')
            ->assertOk()
            ->assertExactJson(['authenticated' => true]);

        $this->assertDatabaseHas('users', [
            'firebase_id' => 'firebase-uid-route',
            'email' => 'route@example.com',
        ]);
    }

    #[Test]
    public function posting_without_a_token_is_rejected()
    {
        $this->postJson('/auth/firebase')
            ->assertStatus(401)
            ->assertExactJson(['error' => 'Missing Firebase ID token.']);
    }

    #[Test]
    public function deleting_the_session_logs_the_user_out()
    {
        $user = User::create([
            'firebase_id' => 'firebase-uid-logout',
            'email' => 'logout-route@example.com',
            'name' => 'Logout Route User',
        ]);

        $this->actingAs($user, 'web')
            ->deleteJson('/auth/firebase')
            ->assertOk()
            ->assertExactJson(['authenticated' => false]);
    }
}
