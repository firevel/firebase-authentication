<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\FirebaseAuthenticationServiceProvider;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Kreait\Firebase\JWT\IdTokenVerifier;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\Psr16Adapter;

class CacheBackendTest extends TestCase
{
    /** @test */
    public function it_defaults_to_filesystem_cache()
    {
        $cache = $this->callProtectedResolveCache();

        $this->assertInstanceOf(FilesystemAdapter::class, $cache);
    }

    /** @test */
    public function it_wraps_a_laravel_cache_store_when_configured()
    {
        config(['firebase-authentication.cache.store' => 'array']);

        $cache = $this->callProtectedResolveCache();

        $this->assertInstanceOf(Psr16Adapter::class, $cache);
    }

    /** @test */
    public function it_produces_a_working_verifier_either_way()
    {
        $this->app->forgetInstance(IdTokenVerifier::class);
        config(['firebase-authentication.cache.store' => 'array']);

        $this->assertInstanceOf(IdTokenVerifier::class, app(IdTokenVerifier::class));
    }

    private function callProtectedResolveCache()
    {
        $provider = new FirebaseAuthenticationServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'resolveCache');
        $method->setAccessible(true);

        return $method->invoke($provider);
    }
}
