<?php

namespace Fuisic\Auth\Http\Controllers;

use Fuisic\Auth\Passkeys\PasskeyOptionsStore;
use Fuisic\Auth\Services\AuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use Throwable;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Passkeys поверх laravel/passkeys для API на Sanctum-токенах: опции церемоний
 * хранятся в кэше (PasskeyOptionsStore), а не в сессии.
 */
class PasskeyController extends Controller
{
    public function __construct(
        private readonly PasskeyOptionsStore $options,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $passkeys = $request->user()->passkeys()
            ->select(['id', 'name', 'last_used_at', 'created_at'])
            ->latest()
            ->get();

        return response()->json(['passkeys' => $passkeys]);
    }

    public function registerOptions(Request $request, GenerateRegistrationOptions $generate): JsonResponse
    {
        $options = $generate($request->user());
        $this->options->put($options);

        return response()->json(['options' => WebAuthn::toBrowserArray($options)]);
    }

    public function register(Request $request, StorePasskey $store): JsonResponse
    {
        $request->validate(['name' => ['nullable', 'string', 'max:255']]);
        $credential = $this->credential($request);

        $options = $this->options->pull($credential, PublicKeyCredentialCreationOptions::class)
            ?? $this->expired();

        $this->attempt(fn () => $store(
            $request->user(),
            $request->string('name')->trim()->value() ?: __('fuisic-auth::auth.passkey_default_name'),
            $credential,
            $options,
        ));

        return response()->json(['message' => __('fuisic-auth::auth.passkey_registered')], 201);
    }

    public function loginOptions(GenerateVerificationOptions $generate): JsonResponse
    {
        $options = $generate();
        $this->options->put($options);

        return response()->json(['options' => WebAuthn::toBrowserArray($options)]);
    }

    public function login(Request $request, VerifyPasskey $verify, AuthTokenService $tokens): JsonResponse
    {
        $credential = $this->credential($request);

        $options = $this->options->pull($credential, PublicKeyCredentialRequestOptions::class)
            ?? $this->expired();

        $passkey = $this->attempt(fn () => $verify($credential, $options));
        $user = $passkey->user;

        if (! Passkeys::allowsLogin($request, $passkey)) {
            return response()->json(['message' => __('fuisic-auth::auth.passkey_login_failed')], 401);
        }

        return response()->json([
            'token' => $tokens->issue($user),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at ?? null,
            ],
        ]);
    }

    public function destroy(Request $request, string $id, DeletePasskey $delete): JsonResponse
    {
        $passkey = $request->user()->passkeys()->find($id);

        if (! $passkey) {
            return response()->json(['message' => __('fuisic-auth::auth.passkey_not_found')], 404);
        }

        $delete($request->user(), $passkey);

        return response()->json(['message' => __('fuisic-auth::auth.passkey_removed')]);
    }

    private function credential(Request $request): PublicKeyCredential
    {
        $request->validate([
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
        ]);

        try {
            return WebAuthn::fromJson(json_encode($request->input('credential')) ?: '{}', PublicKeyCredential::class);
        } catch (Throwable) {
            throw ValidationException::withMessages(['credential' => __('fuisic-auth::auth.passkey_invalid')]);
        }
    }

    /**
     * @template T
     *
     * @param  callable(): T  $ceremony
     * @return T
     */
    private function attempt(callable $ceremony): mixed
    {
        try {
            return $ceremony();
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            // ошибки проверки подписи/attestation из webauthn-lib — это невалидный credential, а не 500
            report($e);

            throw ValidationException::withMessages(['credential' => __('fuisic-auth::auth.passkey_invalid')]);
        }
    }

    private function expired(): never
    {
        throw ValidationException::withMessages(['credential' => __('fuisic-auth::auth.passkey_expired')]);
    }
}
