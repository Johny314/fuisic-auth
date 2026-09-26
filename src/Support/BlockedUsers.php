<?php

namespace Fuisic\Auth\Support;

use DateTimeInterface;
use Fuisic\Auth\Exceptions\UserBlockedException;
use Illuminate\Support\Carbon;

/**
 * Проверка блокировки через хук модели authBlock() (см. HasFuisicAuth):
 * пакет не знает, как приложение хранит блокировки.
 */
class BlockedUsers
{
    /**
     * @return array{reason: ?string, until: ?DateTimeInterface}|null
     */
    public static function blockOf(mixed $user): ?array
    {
        if (! is_object($user) || ! method_exists($user, 'authBlock')) {
            return null;
        }

        $block = $user->authBlock();

        if ($block === null) {
            return null;
        }

        $until = $block['until'] ?? null;

        return [
            'reason' => $block['reason'] ?? null,
            'until' => is_string($until) ? Carbon::parse($until) : $until,
        ];
    }

    /**
     * @throws UserBlockedException
     */
    public static function ensureNotBlocked(mixed $user): void
    {
        $block = self::blockOf($user);

        if ($block !== null) {
            throw new UserBlockedException($block['reason'], $block['until']);
        }
    }
}
