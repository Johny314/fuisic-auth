<?php

namespace Fuisic\Auth\WebAuthn;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Laragear\WebAuthn\Assertion\Creator\AssertionCreation;
use Laragear\WebAuthn\Assertion\Validator\AssertionValidation;
use Laragear\WebAuthn\Attestation\Creator\AttestationCreation;
use Laragear\WebAuthn\Attestation\Validator\AttestationValidation;
use Laragear\WebAuthn\ByteBuffer;
use Laragear\WebAuthn\Challenge\Challenge;
use Laragear\WebAuthn\Contracts\WebAuthnChallengeRepository;

class CacheChallengeRepository implements WebAuthnChallengeRepository
{
    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function store(AttestationCreation|AssertionCreation $ceremony, Challenge $challenge): void
    {
        $this->cache->put(
            $this->key($challenge->data->getBinaryString()),
            $challenge,
            $challenge->timeout + 5,
        );
    }

    public function pull(AttestationValidation|AssertionValidation $ceremony): ?Challenge
    {
        $binary = $this->challengeFromClientData($ceremony->json->get('response.clientDataJSON'));

        if ($binary === null) {
            return null;
        }

        $challenge = $this->cache->pull($this->key($binary));

        return $challenge instanceof Challenge && $challenge->isValid() ? $challenge : null;
    }

    private function challengeFromClientData(mixed $encoded): ?string
    {
        if (! is_string($encoded) || $encoded === '') {
            return null;
        }

        $json = ByteBuffer::decodeBase64Url($encoded);

        if (! is_string($json) || $json === '') {
            return null;
        }

        $payload = json_decode($json, true);

        if (! is_array($payload) || ! is_string($payload['challenge'] ?? null)) {
            return null;
        }

        $binary = ByteBuffer::decodeBase64Url($payload['challenge']);

        return is_string($binary) && $binary !== '' ? $binary : null;
    }

    private function key(string $binary): string
    {
        return 'fuisic-auth.webauthn.'.hash('sha256', $binary);
    }
}
