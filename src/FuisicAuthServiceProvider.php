<?php

namespace Fuisic\Auth;

use Fuisic\Auth\Listeners\SocialiteWasCalledListener;
use Fuisic\Auth\WebAuthn\CacheChallengeRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laragear\WebAuthn\Contracts\WebAuthnChallengeRepository;
use SocialiteProviders\Manager\SocialiteWasCalled;

class FuisicAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fuisic-auth.php', 'fuisic-auth');

        $this->app->bind(WebAuthnChallengeRepository::class, CacheChallengeRepository::class);
    }

    public function boot(): void
    {
        $this->syncWebAuthnConfig();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'fuisic-auth');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'fuisic-auth');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/fuisic-auth.php' => config_path('fuisic-auth.php'),
            ], 'fuisic-auth-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'fuisic-auth-migrations');
        }

        Event::listen(SocialiteWasCalled::class, SocialiteWasCalledListener::class);

        $prefix = config('fuisic-auth.route_prefix');

        Route::group([
            'prefix' => $prefix !== '' ? $prefix : null,
            'middleware' => config('fuisic-auth.middleware'),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/auth.php');
        });
    }

    private function syncWebAuthnConfig(): void
    {
        $rpId = config('fuisic-auth.passkeys.relying_party.id')
            ?: parse_url((string) config('app.url'), PHP_URL_HOST);

        $rpName = config('fuisic-auth.passkeys.relying_party.name') ?: config('app.name');
        $frontend = rtrim((string) config('fuisic-auth.frontend_url'), '/');
        $origins = config('webauthn.origins') ?: $frontend;

        config([
            'webauthn.relying_party.id' => $rpId,
            'webauthn.relying_party.name' => $rpName,
            'webauthn.origins' => $origins,
        ]);
    }
}
