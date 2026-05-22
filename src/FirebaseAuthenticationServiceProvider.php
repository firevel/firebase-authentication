<?php

namespace Firevel\FirebaseAuthentication;

use Firevel\FirebaseAuthentication\Contracts\TokenVerifier;
use Illuminate\Auth\RequestGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Kreait\Firebase\JWT\IdTokenVerifier;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\Psr16Adapter;

class FirebaseAuthenticationServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations/add_firebase_columns_to_users_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_add_firebase_columns_to_users_table.php'),
            ], 'firebase-authentication-migrations');

            $this->publishes([
                __DIR__.'/../config/firebase-authentication.php' => config_path('firebase-authentication.php'),
            ], 'firebase-authentication-config');
        }

        Auth::extend('firebase', function ($app, $name, array $config) {
            $provider = $config['provider'] ?? 'users';
            $model = config("auth.providers.{$provider}.model");

            return new RequestGuard(function ($request) use ($model) {
                return app(FirebaseGuard::class)->user($request, $model);
            }, $app['request']);
        });

        if (config('firebase-authentication.session.enabled', true)) {
            Route::middleware(config('firebase-authentication.session.middleware', 'web'))
                ->group(__DIR__.'/../routes/firebase-session.php');
        }
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/firebase-authentication.php',
            'firebase-authentication'
        );

        $this->app->singleton(IdTokenVerifier::class, function ($app) {
            $project = config('firebase-authentication.project_id')
                ?? config('firebase.project_id')
                ?? env('GOOGLE_CLOUD_PROJECT');

            if (empty($project)) {
                throw new \Exception('Missing GOOGLE_CLOUD_PROJECT env variable.');
            }

            return IdTokenVerifier::createWithProjectIdAndCache($project, $this->resolveCache());
        });

        $this->app->singleton(TokenVerifier::class, function ($app) {
            return new KreaitTokenVerifier($app->make(IdTokenVerifier::class));
        });
    }

    protected function resolveCache(): CacheItemPoolInterface
    {
        $store = config('firebase-authentication.cache.store');

        if (! empty($store)) {
            return new Psr16Adapter(Cache::store($store));
        }

        return new FilesystemAdapter(
            'firebase-token-cache',
            0,
            config('firebase-authentication.cache.path') ?: null
        );
    }
}
