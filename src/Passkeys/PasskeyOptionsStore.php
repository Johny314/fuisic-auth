<?php

namespace Fuisic\Auth\Passkeys;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Хранит опции WebAuthn-церемонии между запросами без сессии (API на токенах).
 *
 * Ключ — сам challenge: браузер возвращает его в clientDataJSON, поэтому клиенту
 * не нужно передавать отдельный идентификатор. Опции выдаются один раз (pull).
 */
final class PasskeyOptionsStore
{
    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function put(PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options): void
    {
        $this->cache->put(
            $this->key($options->challenge),
            WebAuthn::toJson($options),
            now()->addMilliseconds(Passkeys::timeout())->addSeconds(5),
        );
    }

    /**
     * @template T of PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions
     *
     * @param  class-string<T>  $class
     * @return T|null
     */
    public function pull(PublicKeyCredential $credential, string $class): ?object
    {
        $challenge = $credential->response->clientDataJSON->challenge;
        $serialized = $this->cache->pull($this->key($challenge));

        return is_string($serialized) ? WebAuthn::fromJson($serialized, $class) : null;
    }

    private function key(string $challenge): string
    {
        return 'fuisic-auth:passkey-options:'.hash('sha256', $challenge);
    }
}
