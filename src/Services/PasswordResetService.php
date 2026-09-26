<?php

namespace Fuisic\Auth\Services;

use Fuisic\Auth\Jobs\SendPasswordResetEmailJob;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function sendResetLink(string $email): string
    {
        // Колбэк ничего не возвращает: иначе брокер примет результат за статус (null → RESET_LINK_SENT)
        $status = Password::sendResetLink(
            ['email' => $email],
            function ($user, string $token): void {
                SendPasswordResetEmailJob::dispatch($user, $token)
                    ->onConnection(config('fuisic-auth.queue.connection'))
                    ->onQueue(config('fuisic-auth.queue.password_reset'));
            }
        );

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    public function reset(string $email, string $token, string $password): string
    {
        $status = Password::reset(
            [
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'token' => $token,
            ],
            function ($user) use ($password) {
                $user->forceFill(['password' => Hash::make($password)]);

                // Новый remember-токен завершает «Запомнить меня» на других устройствах
                if ($this->supportsRememberToken($user)) {
                    $user->setRememberToken(Str::random(60));
                }

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    /**
     * Пакет переиспользуемый: колонки remember_token в таблице пользователей может не быть.
     */
    private function supportsRememberToken(Model $user): bool
    {
        $column = $user->getRememberTokenName();

        return filled($column)
            && $user->getConnection()->getSchemaBuilder()->hasColumn($user->getTable(), $column);
    }
}
