<?php

namespace Fuisic\Auth\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $frontend = rtrim((string) config('fuisic-auth.frontend_url'), '/');
        $url = $frontend.'/reset-password?token='.$this->token.'&email='.urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject(__('fuisic-auth::auth.reset_password_subject'))
            ->view('fuisic-auth::mail.reset-password', [
                'url' => $url,
                'action' => __('fuisic-auth::auth.reset_password_action'),
                'expire' => config('fuisic-auth.password_reset.expire'),
            ]);
    }
}
