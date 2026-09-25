<?php

use Fuisic\Auth\Http\Controllers\EmailVerificationController;
use Fuisic\Auth\Http\Controllers\LoginController;
use Fuisic\Auth\Http\Controllers\LogoutController;
use Fuisic\Auth\Http\Controllers\MeController;
use Fuisic\Auth\Http\Controllers\OAuthController;
use Fuisic\Auth\Http\Controllers\PasskeyController;
use Fuisic\Auth\Http\Controllers\PasswordResetController;
use Fuisic\Auth\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

// Защита от перебора паролей и спама письмами
Route::middleware(config('fuisic-auth.throttle'))->group(function () {
    Route::post('register', RegisterController::class)->name('fuisic-auth.register');
    Route::post('login', LoginController::class)->name('fuisic-auth.login');
    Route::post('password/forgot', [PasswordResetController::class, 'forgot'])->name('fuisic-auth.password.forgot');
    Route::post('password/reset', [PasswordResetController::class, 'reset'])->name('fuisic-auth.password.reset');

    if (config('fuisic-auth.passkeys.enabled')) {
        Route::post('passkeys/login/options', [PasskeyController::class, 'loginOptions'])->name('fuisic-auth.passkeys.login.options');
        Route::post('passkeys/login', [PasskeyController::class, 'login'])->name('fuisic-auth.passkeys.login');
    }
});

Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->name('fuisic-auth.verification.verify');

Route::get('oauth/{provider}/redirect', [OAuthController::class, 'redirect'])->name('fuisic-auth.oauth.redirect');
Route::get('oauth/{provider}/callback', [OAuthController::class, 'callback'])->name('fuisic-auth.oauth.callback');

Route::middleware(config('fuisic-auth.auth_middleware'))->group(function () {
    Route::post('logout', LogoutController::class)->name('fuisic-auth.logout');
    Route::get('me', MeController::class)->name('fuisic-auth.me');

    Route::post('email/verify/resend', [EmailVerificationController::class, 'resend'])->name('fuisic-auth.verification.resend');

    Route::put('password', [PasswordResetController::class, 'update'])->name('fuisic-auth.password.update');

    Route::get('oauth/{provider}/link', [OAuthController::class, 'redirect'])
        ->name('fuisic-auth.oauth.link');

    Route::get('oauth/linked', [OAuthController::class, 'linked'])->name('fuisic-auth.oauth.linked');
    Route::delete('oauth/{provider}', [OAuthController::class, 'unlink'])->name('fuisic-auth.oauth.unlink');

    if (config('fuisic-auth.passkeys.enabled')) {
        Route::get('passkeys', [PasskeyController::class, 'index'])->name('fuisic-auth.passkeys.index');
        Route::post('passkeys/register/options', [PasskeyController::class, 'registerOptions'])->name('fuisic-auth.passkeys.register.options');
        Route::post('passkeys/register', [PasskeyController::class, 'register'])->name('fuisic-auth.passkeys.register');
        Route::delete('passkeys/{id}', [PasskeyController::class, 'destroy'])->name('fuisic-auth.passkeys.destroy');
    }
});
