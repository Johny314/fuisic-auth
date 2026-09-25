<?php

namespace Fuisic\Auth\Traits;

use Fuisic\Auth\Jobs\SendVerificationEmailJob;
use Fuisic\Auth\Notifications\ResetPasswordNotification;
use Laragear\WebAuthn\WebAuthnAuthentication;

trait HasFuisicAuth
{
    use HasOAuthAccounts;
    use WebAuthnAuthentication;

    public function sendEmailVerificationNotification(): void
    {
        SendVerificationEmailJob::dispatch($this)
            ->onConnection(config('fuisic-auth.queue.connection'))
            ->onQueue(config('fuisic-auth.queue.verification'));
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
