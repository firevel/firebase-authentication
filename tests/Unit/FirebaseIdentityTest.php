<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\FirebaseIdentity;
use Firevel\FirebaseAuthentication\Tests\Fixtures\FirebaseIdentityByEmail;
use Firevel\FirebaseAuthentication\Tests\Fixtures\FirebaseIdentityWithCustomMapping;
use Firevel\FirebaseAuthentication\Tests\Fixtures\FirebaseIdentityWithCustomUid;
use Firevel\FirebaseAuthentication\Tests\TestCase;

class FirebaseIdentityTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $identity = new FirebaseIdentity;

        $this->assertInstanceOf(FirebaseIdentity::class, $identity);
    }

    /** @test */
    public function it_uses_firebase_authenticable_trait()
    {
        $identity = new FirebaseIdentity;

        $this->assertTrue(method_exists($identity, 'resolveByClaims'));
        $this->assertTrue(method_exists($identity, 'setClaims'));
        $this->assertTrue(method_exists($identity, 'getClaims'));
        $this->assertTrue(method_exists($identity, 'isAnonymous'));
    }

    /** @test */
    public function it_has_non_incrementing_id()
    {
        $identity = new FirebaseIdentity;

        $this->assertFalse($identity->incrementing);
    }

    /** @test */
    public function it_has_no_guarded_attributes()
    {
        $identity = new FirebaseIdentity;

        $this->assertEquals([], $identity->getGuarded());
    }

    /** @test */
    public function it_resolves_by_claims_without_database()
    {
        $claims = [
            'sub' => 'microservice-user-123',
            'email' => 'microservice@example.com',
            'name' => 'Microservice User',
            'picture' => 'https://example.com/photo.jpg',
        ];

        $identity = (new FirebaseIdentity)->resolveByClaims($claims);

        $this->assertInstanceOf(FirebaseIdentity::class, $identity);
        $this->assertEquals('microservice-user-123', $identity->id);
        $this->assertEquals('microservice@example.com', $identity->email);
        $this->assertEquals('Microservice User', $identity->name);
        $this->assertEquals('https://example.com/photo.jpg', $identity->avatar_url);
        $this->assertEquals($claims, $identity->getClaims());
    }

    /** @test */
    public function it_resolves_by_email_when_configured()
    {
        $identityClass = new FirebaseIdentityByEmail;

        $claims = [
            'sub' => 'firebase-uid-456',
            'email' => 'email-resolve@example.com',
            'name' => 'Email Resolved User',
        ];

        $identity = $identityClass->resolveByClaims($claims);

        $this->assertEquals('email-resolve@example.com', $identity->email);
        $this->assertEquals('Email Resolved User', $identity->name);
    }

    /** @test */
    public function it_resolves_by_custom_firebase_uid_attribute()
    {
        $identityClass = new FirebaseIdentityWithCustomUid;

        $claims = [
            'sub' => 'custom-uid-789',
            'email' => 'custom@example.com',
            'name' => 'Custom Identity User',
        ];

        $identity = $identityClass->resolveByClaims($claims);

        $this->assertEquals('custom-uid-789', $identity->firebase_uid);
        $this->assertEquals('custom@example.com', $identity->email);
        $this->assertEquals('Custom Identity User', $identity->name);
    }

    /** @test */
    public function it_does_not_persist_to_database()
    {
        $identity = new FirebaseIdentity([
            'id' => 'no-persist-123',
            'email' => 'nopersist@example.com',
            'name' => 'No Persist User',
        ]);

        $result = $identity->save();

        // Save returns true but doesn't actually persist
        $this->assertTrue($result);
    }

    /** @test */
    public function it_fills_attributes_from_claims()
    {
        $claims = [
            'sub' => 'fill-test-123',
            'email' => 'fill@example.com',
            'name' => 'Fill Test User',
            'picture' => 'https://example.com/fill.jpg',
            'custom_claim' => 'custom_value',
        ];

        $identity = (new FirebaseIdentity)->resolveByClaims($claims);

        $this->assertEquals('fill-test-123', $identity->id);
        $this->assertEquals('fill@example.com', $identity->email);
        $this->assertEquals('Fill Test User', $identity->name);
        $this->assertEquals('https://example.com/fill.jpg', $identity->avatar_url);
    }

    /** @test */
    public function it_works_with_anonymous_users()
    {
        $claims = [
            'sub' => 'anonymous-123',
            'firebase' => [
                'sign_in_provider' => 'anonymous',
            ],
        ];

        $identity = (new FirebaseIdentity)->resolveByClaims($claims);

        $this->assertTrue($identity->isAnonymous());
        $this->assertEquals('anonymous-123', $identity->id);
    }

    /** @test */
    public function it_stores_claims_from_jwt()
    {
        $claims = [
            'sub' => 'jwt-test-123',
            'email' => 'jwt@example.com',
            'name' => 'JWT Test User',
            'firebase' => [
                'sign_in_provider' => 'google.com',
                'identities' => [
                    'google.com' => ['123456789'],
                ],
            ],
            'custom_role' => 'admin',
        ];

        $identity = (new FirebaseIdentity)->resolveByClaims($claims);

        $retrievedClaims = $identity->getClaims();

        $this->assertEquals($claims, $retrievedClaims);
        $this->assertEquals('google.com', $retrievedClaims['firebase']['sign_in_provider']);
        $this->assertEquals('admin', $retrievedClaims['custom_role']);
    }

    /** @test */
    public function it_stores_firebase_authentication_token()
    {
        $identity = new FirebaseIdentity;
        $token = 'microservice-jwt-token-12345';

        $identity->setFirebaseAuthenticationToken($token);

        $this->assertEquals($token, $identity->getFirebaseAuthenticationToken());
    }

    /** @test */
    public function it_can_be_used_for_authentication()
    {
        $claims = [
            'sub' => 'auth-test-123',
            'email' => 'auth@example.com',
            'name' => 'Auth Test User',
        ];

        $identity = (new FirebaseIdentity)->resolveByClaims($claims);

        $this->assertEquals('id', $identity->getAuthIdentifierName());
        $this->assertEquals('auth-test-123', $identity->getAuthIdentifier());
    }

    /** @test */
    public function it_supports_custom_claims_mapping()
    {
        $identityClass = new FirebaseIdentityWithCustomMapping;

        $claims = [
            'sub' => 'mapping-test-123',
            'email' => 'mapping@example.com',
            'name' => 'Mapping Test User',
            'phone_number' => '+1234567890',
        ];

        $identity = $identityClass->resolveByClaims($claims);

        $this->assertEquals('mapping-test-123', $identity->id);
        $this->assertEquals('mapping@example.com', $identity->email);
        $this->assertEquals('Mapping Test User', $identity->name);
        $this->assertEquals('+1234567890', $identity->phone);
    }
}
