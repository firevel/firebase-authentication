<?php

namespace Firevel\FirebaseAuthentication\Tests;

use Firevel\FirebaseAuthentication\FirebaseAuthenticationServiceProvider;
use Firevel\FirebaseAuthentication\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            FirebaseAuthenticationServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default environment configuration
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup Firebase configuration
        putenv('GOOGLE_CLOUD_PROJECT=test-project-id');
        $app['config']->set('firebase.project_id', 'test-project-id');

        // Setup auth configuration
        $app['config']->set('auth.defaults.guard', 'api');
        $app['config']->set('auth.guards.api', [
            'driver' => 'firebase',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => User::class,
        ]);
    }
}
