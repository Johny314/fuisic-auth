<?php

namespace Fuisic\Auth\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'fuisic-auth.verification.verify',
            now()->addMinutes((int) config('fuisic-auth.verification.expire')),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('fuisic-auth::auth.verify_email_subject'))
            ->view('fuisic-auth::mail.verify-email', [
                'name' => $notifiable->name ?? '',
                'url' => $this->verificationUrl($notifiable),
                'intro' => __('fuisic-auth::auth.verify_email_line'),
                'action' => __('fuisic-auth::auth.verify_email_action'),
                'expire' => config('fuisic-auth.verification.expire'),
            ]);
    }
}
