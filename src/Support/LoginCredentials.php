<?php

namespace Fuisic\Auth\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Разбор полей входа: `login` (email или логин) или старое поле `email`.
 * Значение с `@` — email, иначе логин в колонке `login.username_column` (без учёта регистра).
 */
final class LoginCredentials
{
    private function __construct(
        private readonly string $identifier,
        private readonly string $password,
        private readonly ?string $usernameColumn,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $usernameColumn = config('fuisic-auth.login.username_column') ?: null;

        $validated = $request->validate([
            'login' => array_merge(
                ['required_without:email', 'nullable', 'string', 'max:255'],
                $usernameColumn === null ? ['email'] : [],
            ),
            'email' => ['required_without:login', 'nullable', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        return new self(
            trim((string) ($validated['login'] ?? $validated['email'])),
            $validated['password'],
            $usernameColumn,
        );
    }

    public function viaEmail(): bool
    {
        return $this->usernameColumn === null || str_contains($this->identifier, '@');
    }

    /**
     * Credentials для Auth::attempt().
     */
    public function credentials(): array
    {
        if ($this->viaEmail()) {
            return ['email' => $this->identifier, 'password' => $this->password];
        }

        $column = $this->usernameColumn;
        $username = mb_strtolower($this->identifier);

        return [
            $column => fn (Builder $query) => $query->whereRaw(
                'lower('.$query->getQuery()->getGrammar()->wrap($column).') = ?',
                [$username],
            ),
            'password' => $this->password,
        ];
    }
}
