<?php

namespace Fuisic\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(array_merge([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'avatar_url' => $user->avatar_url ?? null,
            'oauth_providers' => method_exists($user, 'oauthAccounts')
                ? $user->oauthAccounts()->select(['provider', 'provider_email', 'avatar'])->get()
                : [],
            'has_password' => ! empty($user->password),
        ], method_exists($user, 'authProfile') ? $user->authProfile() : []));
    }
}
