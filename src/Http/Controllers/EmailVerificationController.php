<?php

namespace Fuisic\Auth\Http\Controllers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash): JsonResponse|RedirectResponse
    {
        $frontend = rtrim((string) config('fuisic-auth.frontend_url'), '/');
        $wantsJson = $request->expectsJson();

        if (! URL::hasValidSignature($request)) {
            return $this->verifiedResponse($wantsJson, $frontend, 'invalid', __('fuisic-auth::auth.verification_invalid'), 403);
        }

        $userModel = \Fuisic\Auth\Support\UserModel::class();
        $user = $userModel::query()->findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return $this->verifiedResponse($wantsJson, $frontend, 'invalid', __('fuisic-auth::auth.verification_invalid'), 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->verifiedResponse($wantsJson, $frontend, 'already', __('fuisic-auth::auth.email_already_verified'));
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->verifiedResponse($wantsJson, $frontend, 'ok', __('fuisic-auth::auth.email_verified'));
    }

    public function resend(Request $request, \Fuisic\Auth\Services\EmailVerificationService $verification): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => __('fuisic-auth::auth.email_already_verified')]);
        }

        $verification->send($request->user());

        return response()->json(['message' => __('fuisic-auth::auth.verification_sent')]);
    }

    private function verifiedResponse(
        bool $wantsJson,
        string $frontend,
        string $status,
        string $message,
        int $code = 200,
    ): JsonResponse|RedirectResponse {
        if ($wantsJson) {
            return response()->json(['message' => $message], $code);
        }

        return redirect()->away($frontend.'/auth/verified?status='.$status);
    }
}
