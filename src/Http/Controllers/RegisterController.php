<?php

namespace Fuisic\Auth\Http\Controllers;

use Fuisic\Auth\Jobs\SendVerificationEmailJob;
use Fuisic\Auth\Services\AuthTokenService;
use Fuisic\Auth\Services\EmailVerificationService;
use Fuisic\Auth\Support\UserModel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function __invoke(Request $request, AuthTokenService $tokens, EmailVerificationService $verification): JsonResponse
    {
        $roles = config('fuisic-auth.register.roles', []);

        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], $roles === [] ? [] : [
            'role' => ['sometimes', 'string', Rule::in($roles)],
        ], config('fuisic-auth.register.validation', [])));

        $userModel = UserModel::class();
        $attributes = array_merge(
            config('fuisic-auth.register.defaults', []),
            collect($validated)->only(array_merge(
                ['name', 'email'],
                config('fuisic-auth.register.fillable', [])
            ))->all(),
            ['password' => Hash::make($validated['password'])]
        );
        $role = $validated['role'] ?? config('fuisic-auth.register.default_role');

        $user = DB::transaction(function () use ($userModel, $attributes, $role) {
            $user = $userModel::query()->create($attributes);

            if ($role !== null && method_exists($user, 'assignRegistrationRole')) {
                $user->assignRegistrationRole($role);
            }

            return $user;
        });

        if ($user instanceof MustVerifyEmail) {
            SendVerificationEmailJob::dispatch($user)
                ->onConnection(config('fuisic-auth.queue.connection'))
                ->onQueue(config('fuisic-auth.queue.verification'));

            return response()->json([
                'message' => __('fuisic-auth::auth.registered_verify_email'),
            ], 201);
        }

        return response()->json([
            'token' => $tokens->issue($user),
            'user' => $this->userPayload($user),
        ], 201);
    }

    private function userPayload(object $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
        ];
    }
}
