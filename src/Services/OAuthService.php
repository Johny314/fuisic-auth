<?php

namespace Fuisic\Auth\Services;

use Fuisic\Auth\Enums\OAuthProvider;
use Fuisic\Auth\Models\OAuthAccount;
use Fuisic\Auth\Support\BlockedUsers;
use Fuisic\Auth\Support\UserModel;
use Fuisic\Auth\Traits\HasOAuthAccounts;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OAuthService
{
    public function __construct(
        private readonly AuthTokenService $tokens,
    ) {}

    public function redirectUrl(OAuthProvider $provider, ?int $linkUserId = null): string
    {
        $state = $this->tokens->createOAuthState([
            'provider' => $provider->value,
            'intent' => $linkUserId ? 'link' : 'login',
            'user_id' => $linkUserId,
            'nonce' => Str::random(40),
        ]);

        return Socialite::driver($provider->socialiteDriver())
            ->stateless()
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();
    }

    public function handleCallback(string $provider, string $state): array
    {
        $providerEnum = OAuthProvider::tryFromEnabled($provider)
            ?? throw new BadRequestHttpException(__('fuisic-auth::auth.oauth_provider_disabled'));

        $stateData = $this->tokens->parseOAuthState($state);

        if ($stateData['provider'] !== $providerEnum->value) {
            throw new BadRequestHttpException(__('fuisic-auth::auth.oauth_state_invalid'));
        }

        $socialiteUser = Socialite::driver($providerEnum->socialiteDriver())
            ->stateless()
            ->user();

        if ($stateData['intent'] === 'link') {
            $user = $this->resolveUserModel()::query()->findOrFail($stateData['user_id']);
            BlockedUsers::ensureNotBlocked($user);
            $this->linkAccount($user, $providerEnum, $socialiteUser);

            return [
                'linked' => true,
                'provider' => $providerEnum->value,
            ];
        }

        $user = $this->loginOrRegister($providerEnum, $socialiteUser);
        BlockedUsers::ensureNotBlocked($user);
        $token = $this->tokens->issue($user);

        return [
            'token' => $token,
            'user' => $this->serializeUser($user),
        ];
    }

    public function linkAccount(Authenticatable $user, OAuthProvider $provider, SocialiteUser $socialiteUser): OAuthAccount
    {
        $this->assertUserSupportsOAuth($user);

        $existing = OAuthAccount::query()
            ->where('provider', $provider->value)
            ->where('provider_id', $socialiteUser->getId())
            ->first();

        if ($existing && $existing->user_id !== $user->getAuthIdentifier()) {
            throw new BadRequestHttpException(__('fuisic-auth::auth.oauth_already_linked_other'));
        }

        return $user->oauthAccounts()->updateOrCreate(
            ['provider' => $provider->value],
            $this->mapSocialiteUser($provider, $socialiteUser)
        );
    }

    public function unlinkAccount(Authenticatable $user, OAuthProvider $provider): void
    {
        $this->assertUserSupportsOAuth($user);

        if (! $user->password && $user->oauthAccounts()->count() <= 1) {
            throw new BadRequestHttpException(__('fuisic-auth::auth.cannot_unlink_last_method'));
        }

        $user->oauthAccounts()->where('provider', $provider->value)->delete();
    }

    private function loginOrRegister(OAuthProvider $provider, SocialiteUser $socialiteUser): Authenticatable
    {
        $account = OAuthAccount::query()
            ->where('provider', $provider->value)
            ->where('provider_id', $socialiteUser->getId())
            ->first();

        if ($account) {
            $account->update($this->mapSocialiteUser($provider, $socialiteUser));

            return $account->user;
        }

        $userModel = $this->resolveUserModel();

        return DB::transaction(function () use ($provider, $socialiteUser, $userModel) {
            $email = $socialiteUser->getEmail();

            $user = $email
                ? $userModel::query()->where('email', $email)->first()
                : null;

            if (! $user) {
                $user = $userModel::query()->create([
                    'name' => $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? 'User',
                    'email' => $email,
                    'email_verified_at' => $email ? now() : null,
                    'password' => null,
                ]);

                $role = config('fuisic-auth.register.default_role');

                if ($role !== null && method_exists($user, 'assignRegistrationRole')) {
                    $user->assignRegistrationRole($role);
                }
            }

            $this->linkAccount($user, $provider, $socialiteUser);

            return $user->fresh();
        });
    }

    private function mapSocialiteUser(OAuthProvider $provider, SocialiteUser $socialiteUser): array
    {
        return [
            'provider' => $provider->value,
            'provider_id' => (string) $socialiteUser->getId(),
            'provider_email' => $socialiteUser->getEmail(),
            'avatar' => $socialiteUser->getAvatar(),
            'token' => $socialiteUser->token ?? null,
            'refresh_token' => $socialiteUser->refreshToken ?? null,
            'token_expires_at' => isset($socialiteUser->expiresIn)
                ? now()->addSeconds((int) $socialiteUser->expiresIn)
                : null,
        ];
    }

    private function assertUserSupportsOAuth(Authenticatable $user): void
    {
        if (! in_array(HasOAuthAccounts::class, class_uses_recursive($user), true)) {
            throw new BadRequestHttpException('User model must use HasOAuthAccounts trait.');
        }
    }

    private function resolveUserModel(): string
    {
        return UserModel::class();
    }

    private function serializeUser(Authenticatable $user): array
    {
        return [
            'id' => $user->getAuthIdentifier(),
            'name' => $user->name ?? null,
            'email' => $user->email ?? null,
            'email_verified_at' => $user->email_verified_at ?? null,
            'oauth_providers' => method_exists($user, 'oauthAccounts')
                ? $user->oauthAccounts()->pluck('provider')->all()
                : [],
        ];
    }
}
