<?php

namespace Fuisic\Auth\Traits;

use Fuisic\Auth\Jobs\SendVerificationEmailJob;
use Fuisic\Auth\Notifications\ResetPasswordNotification;
use Laravel\Passkeys\PasskeyAuthenticatable;

trait HasFuisicAuth
{
    use HasOAuthAccounts;
    use PasskeyAuthenticatable;

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

    /**
     * Имя аккаунта в authenticator'е: email, для аккаунта без email — логин.
     */
    public function getPasskeyUsername(): string
    {
        $column = config('fuisic-auth.login.username_column');

        return $this->getAttribute('email')
            ?? ($column ? $this->getAttribute($column) : null)
            ?? (string) $this->getAuthIdentifier();
    }

    /**
     * Роль нового пользователя (регистрация, первый вход через OAuth).
     * По умолчанию — assignRole() из spatie/laravel-permission, если он подключён.
     */
    public function assignRegistrationRole(string $role): void
    {
        if (method_exists($this, 'assignRole')) {
            $this->assignRole($role);
        }
    }

    /**
     * Дополнительные поля ответа GET /me (роли, права и т.п.) — переопределяется в модели.
     */
    public function authProfile(): array
    {
        return [];
    }

    /**
     * Действующая блокировка: ['reason' => видимая пользователю причина, 'until' => DateTimeInterface|null (бессрочно)]
     * или null. Вход и запросы заблокированного → 403 user_blocked. Переопределяется в модели.
     *
     * @return array{reason: ?string, until: ?\DateTimeInterface}|null
     */
    public function authBlock(): ?array
    {
        return null;
    }
}
