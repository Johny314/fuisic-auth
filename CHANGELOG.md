# Changelog

## Unreleased

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
