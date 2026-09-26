# Changelog

## 3.0.0 - 2026-09-26

- **BREAKING:** Laravel 13 и PHP 8.4+ (`illuminate/* ^13`).
- **BREAKING:** passkeys на [laravel/passkeys](https://github.com/laravel/passkeys-server) вместо заброшенного `laragear/webauthn`:
  - модель: `Laravel\Passkeys\Contracts\PasskeyUser` вместо `WebAuthnAuthenticatable`, провайдер `eloquent` вместо `eloquent-webauthn`;
  - таблица `passkeys` (`vendor:publish --tag=passkeys-migrations`), старые `webauthn_credentials` не переносятся;
  - запросы `{credential}` / `{name, credential}`, опции приходят как `{options}`, список — `{id, name, last_used_at, created_at}`;
  - опции одноразовые (кэш по challenge), невалидный/просроченный passkey → 422.
- `throttle:10,1` на login, register, password/*, passkeys/login (`fuisic-auth.throttle`).
- Зависимости: sanctum 4.3, socialite 5.30, socialiteproviders/manager 4.10, laravel-queue-rabbitmq 15.

## 2.0.0 - 2026-09-26

- **BREAKING:** пакет переименован `fuisic/laravel-auth` → `fuisic/auth`, репозиторий `fuisic-laravel-auth` → `fuisic-auth`. В приложении: `composer remove fuisic/laravel-auth && composer require fuisic/auth`, path repository `../fuisic-auth`. Namespace `Fuisic\Auth\` и конфиг `fuisic-auth.php` не менялись.
- `pint.json` (preset laravel, как в fuisic-back).
- CI: Pint, `composer validate` и тесты fuisic-back против текущей версии пакета.

## 1.1.0 - 2026-09-12

- `/me` отдаёт `user_type` и `avatar_url`
- Брендированные HTML-письма подтверждения email и сброса пароля
- OAuth callback редиректит браузер на фронт с токеном
- Passkeys: кэш челленджей, список и удаление, вход без сессии
- Инструкция по VK OAuth: `docs/VK.md`

## 1.0.1

- Sanctum API: register, login, logout, me
- Email verification через RabbitMQ
- Password reset / update
- OAuth VKontakte и Yandex (login, link, unlink)
- Passkeys (WebAuthn)
- Таблица `oauth_accounts` для привязки провайдеров
- Документация: INSTALLATION, CONFIGURATION, API
