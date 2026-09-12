<?php

namespace Fuisic\Auth\Http\Controllers;

use Fuisic\Auth\Enums\OAuthProvider;
use Fuisic\Auth\Services\OAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OAuthController extends Controller
{
    public function redirect(string $provider, Request $request, OAuthService $oauth): JsonResponse
    {
        $providerEnum = OAuthProvider::tryFromEnabled($provider);

        if ($providerEnum === null) {
            return response()->json(['message' => __('fuisic-auth::auth.oauth_provider_disabled')], 404);
        }

        $isLink = $request->route()?->named('fuisic-auth.oauth.link') ?? $request->boolean('link');
        $linkUserId = $isLink ? $request->user()?->id : null;

        if ($isLink && $linkUserId === null) {
            return response()->json(['message' => __('fuisic-auth::auth.unauthenticated')], 401);
        }

        return response()->json([
            'url' => $oauth->redirectUrl($providerEnum, $linkUserId),
        ]);
    }

    public function callback(string $provider, Request $request, OAuthService $oauth): JsonResponse|RedirectResponse
    {
        $frontend = rtrim((string) config('fuisic-auth.frontend_url'), '/');
        $wantsJson = $request->expectsJson();

        try {
            $state = $request->query('state', $request->input('state'));

            if (! is_string($state) || $state === '') {
                throw new BadRequestHttpException(__('fuisic-auth::auth.oauth_state_invalid'));
            }

            $result = $oauth->handleCallback($provider, $state);

            if ($wantsJson) {
                return response()->json($result);
            }

            if ($result['linked'] ?? false) {
                return redirect()->away($frontend.'/profile/edit?oauth=linked&provider='.urlencode((string) $result['provider']));
            }

            $redirect = config('fuisic-auth.oauth.redirect_after_login') ?: $frontend.'/auth/oauth-callback';

            return redirect()->away($redirect.'?token='.urlencode((string) $result['token']));
        } catch (\Throwable $e) {
            if ($wantsJson) {
                $code = $e instanceof BadRequestHttpException ? 400 : 500;

                return response()->json(['message' => $e->getMessage()], $code);
            }

            return redirect()->away($frontend.'/auth/auth?oauth_error='.urlencode($e->getMessage()));
        }
    }

    public function unlink(string $provider, Request $request, OAuthService $oauth): JsonResponse
    {
        $providerEnum = OAuthProvider::tryFromEnabled($provider);

        if ($providerEnum === null) {
            return response()->json(['message' => __('fuisic-auth::auth.oauth_provider_disabled')], 404);
        }

        $oauth->unlinkAccount($request->user(), $providerEnum);

        return response()->json(['message' => __('fuisic-auth::auth.oauth_unlinked')]);
    }

    public function linked(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'providers' => method_exists($user, 'oauthAccounts')
                ? $user->oauthAccounts()->select(['provider', 'provider_email', 'avatar', 'created_at'])->get()
                : [],
        ]);
    }
}
