<?php

namespace Fuisic\Auth\Http\Controllers;

use Fuisic\Auth\Services\AuthTokenService;
use Fuisic\Auth\Services\EmailVerificationService;
use Fuisic\Auth\Support\BlockedUsers;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __invoke(Request $request, AuthTokenService $tokens, EmailVerificationService $verification): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => __('fuisic-auth::auth.invalid_credentials'),
            ], 401);
        }

        $user = Auth::user();
        BlockedUsers::ensureNotBlocked($user);

        if ($user instanceof MustVerifyEmail) {
            $verification->ensureCanLogin($user);
        }

        return response()->json([
            'token' => $tokens->issue($user),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'oauth_providers' => method_exists($user, 'oauthAccounts')
                    ? $user->oauthAccounts()->pluck('provider')->all()
                    : [],
            ],
        ]);
    }
}
