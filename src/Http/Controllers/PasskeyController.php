<?php

namespace Fuisic\Auth\Http\Controllers;

use Fuisic\Auth\Services\AuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;

class PasskeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $credentials = $request->user()->webAuthnCredentials()
            ->select(['id', 'alias', 'origin', 'created_at', 'updated_at'])
            ->get();

        return response()->json(['passkeys' => $credentials]);
    }

    public function registerOptions(AttestationRequest $request): JsonResponse
    {
        return $request
            ->fastRegistration()
            ->userless()
            ->allowDuplicates()
            ->toCreate()
            ->toResponse($request);
    }

    public function register(AttestedRequest $request): JsonResponse
    {
        $request->save();

        return response()->json(['message' => __('fuisic-auth::auth.passkey_registered')]);
    }

    public function loginOptions(AssertionRequest $request): JsonResponse
    {
        return $request->toVerify(null)->toResponse($request);
    }

    public function login(AssertedRequest $request, AuthTokenService $tokens): JsonResponse
    {
        $credentials = $request->validated();
        $provider = Auth::createUserProvider('users');

        $user = $provider?->retrieveByCredentials($credentials);

        if (! $user || ! $provider->validateCredentials($user, $credentials)) {
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

    public function destroy(Request $request, string $id): JsonResponse
    {
        $deleted = $request->user()->webAuthnCredentials()->whereKey($id)->delete();

        if (! $deleted) {
            return response()->json(['message' => __('fuisic-auth::auth.passkey_removed')], 404);
        }

        return response()->json(['message' => __('fuisic-auth::auth.passkey_removed')]);
    }
}
