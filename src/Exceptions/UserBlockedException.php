<?php

namespace Fuisic\Auth\Exceptions;

use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Пользователь заблокирован: 403 в одном формате на входе и на любых запросах.
 */
class UserBlockedException extends HttpException
{
    public const string CODE = 'user_blocked';

    public function __construct(
        public readonly ?string $reason,
        public readonly ?DateTimeInterface $until,
    ) {
        parent::__construct(403, __('fuisic-auth::auth.user_blocked'));
    }

    /**
     * @return array{message: string, code: string, block: array{reason: ?string, until: ?string}}
     */
    public function payload(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => self::CODE,
            'block' => [
                'reason' => $this->reason,
                'until' => $this->until ? Carbon::instance($this->until)->toJSON() : null,
            ],
        ];
    }

    public function render(): JsonResponse
    {
        return response()->json($this->payload(), 403);
    }
}
