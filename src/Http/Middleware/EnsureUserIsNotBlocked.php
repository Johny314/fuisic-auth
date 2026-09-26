<?php

namespace Fuisic\Auth\Http\Middleware;

use Closure;
use Fuisic\Auth\Support\BlockedUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 403 user_blocked на любой запрос заблокированного пользователя.
 * Без параметров — пользователь запроса (после auth:*), с параметрами — из указанных guard'ов:
 * `fuisic-auth.not-blocked:sanctum` можно повесить и на группу с публичными маршрутами.
 */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $user = $guards === []
            ? $request->user()
            : collect($guards)->map(fn (string $guard) => Auth::guard($guard)->user())->filter()->first();

        BlockedUsers::ensureNotBlocked($user);

        return $next($request);
    }
}
