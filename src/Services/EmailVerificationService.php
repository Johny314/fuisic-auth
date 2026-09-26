<?php

namespace Fuisic\Auth\Services;

use Fuisic\Auth\Jobs\SendVerificationEmailJob;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    public function send(MustVerifyEmail $user): void
    {
        if ($user->hasVerifiedEmail() || ! self::hasEmail($user)) {
            return;
        }

        SendVerificationEmailJob::dispatch($user)
            ->onConnection(config('fuisic-auth.queue.connection'))
            ->onQueue(config('fuisic-auth.queue.verification'));
    }

    public function ensureCanLogin(MustVerifyEmail $user): void
    {
        // без email подтверждать нечего (аккаунт со входом по логину)
        if (! config('fuisic-auth.require_email_verification') || ! self::hasEmail($user)) {
            return;
        }

        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => [__('fuisic-auth::auth.email_not_verified')],
            ])->status(403);
        }
    }

    public static function hasEmail(MustVerifyEmail $user): bool
    {
        return filled($user->getEmailForVerification());
    }
}
