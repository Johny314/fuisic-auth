# Changelog

## [3.2.0](https://github.com/Johny314/fuisic-auth/compare/v3.1.0...v3.2.0) (2026-09-26)


### Возможности

* login by email or username via the login field ([c347133](https://github.com/Johny314/fuisic-auth/commit/c34713304df22bbb3cec149ee801ec76e0a2df61))

## [3.1.0](https://github.com/Johny314/fuisic-auth/compare/v3.0.1...v3.1.0) (2026-09-26)


### Возможности

* role choice on registration and extra /me fields from the user model ([94b489f](https://github.com/Johny314/fuisic-auth/commit/94b489f8b64860ed6ad18e92ae5178ef1e6fd8cf))

## [3.0.1](https://github.com/Johny314/fuisic-auth/compare/v3.0.0...v3.0.1) (2026-09-26)


### Документация

* release badges instead of hand-maintained versions. ([120dbe9](https://github.com/Johny314/fuisic-auth/commit/120dbe9348200986ac733b75f294e1a4af8eeb85))

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
