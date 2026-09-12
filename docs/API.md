# API

Базовый URL задаётся приложением-хостом. По умолчанию префикс пустой — маршруты на корне (`/register`, `/login`).

Все ответы — JSON. Защищённые эндпоинты требуют заголовок:

```
Authorization: Bearer {token}
```

## Регистрация и вход

### POST `/register`

```json
{
  "name": "Иван Иванов",
  "email": "ivan@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

**201** — регистрация успешна, отправлено письмо:

```json
{ "message": "Регистрация успешна. Подтвердите email — письмо отправлено." }
```

Дополнительные поля (например `user_type`) настраиваются в `config/fuisic-auth.php` → `register`.

---

### POST `/login`

```json
{
  "email": "ivan@example.com",
  "password": "secret123"
}
```

**200**:

```json
{
  "token": "1|...",
  "user": {
    "id": 1,
    "name": "Иван Иванов",
    "email": "ivan@example.com",
    "email_verified_at": "2026-05-31T12:00:00.000000Z",
    "oauth_providers": []
  }
}
```

**401** — неверные credentials. **403** — email не подтверждён.

---

### POST `/logout` 🔒

**200**: `{ "message": "Вы вышли из системы." }`

---

### GET `/me` 🔒

**200**:

```json
{
  "id": 1,
  "name": "Иван Иванов",
  "email": "ivan@example.com",
  "email_verified_at": "...",
  "oauth_providers": [
    { "provider": "yandex", "provider_email": "...", "avatar": "..." }
  ],
  "has_password": true
}
```

## Email verification

### GET `/email/verify/{id}/{hash}`

Signed URL из письма. Подтверждает email без авторизации.

### POST `/email/verify/resend` 🔒

Повторная отправка письма.

## Password

### POST `/password/forgot`

```json
{ "email": "ivan@example.com" }
```

### POST `/password/reset`

```json
{
  "email": "ivan@example.com",
  "token": "...",
  "password": "newsecret123",
  "password_confirmation": "newsecret123"
}
```

### PUT `/password` 🔒

```json
{
  "current_password": "secret123",
  "password": "newsecret123",
  "password_confirmation": "newsecret123"
}
```

## OAuth

Провайдеры: `vkontakte`, `yandex`.

### GET `/oauth/{provider}/redirect`

**200**:

```json
{ "url": "https://oauth.vk.com/authorize?..." }
```

Пользователь переходит по `url`, после авторизации провайдер редиректит на callback с `code` и `state`.

### GET `/oauth/{provider}/callback?state=...`

Браузерный callback от провайдера. Редирект на фронт:

- логин: `{FRONTEND_URL}/auth/oauth-callback?token=...`
- привязка: `{FRONTEND_URL}/profile/edit?oauth=linked&provider=...`
- ошибка: `{FRONTEND_URL}/auth/auth?oauth_error=...`

Если запрос с `Accept: application/json`, вместо редиректа отдаётся JSON:

```json
{
  "token": "1|...",
  "user": { "id": 1, "name": "...", "email": "...", "oauth_providers": ["vkontakte"] }
}
```

При привязке (`link`) в JSON: `{ "linked": true, "provider": "vkontakte" }`.

### GET `/oauth/{provider}/link` 🔒

Как redirect, но привязывает провайдер к текущему пользователю.

### GET `/oauth/linked` 🔒

Список привязанных аккаунтов.

### DELETE `/oauth/{provider}` 🔒

Отвязка провайдера. Нельзя отвязать последний способ входа без пароля.

## Passkeys (WebAuthn)

Требует `@simplewebauthn/browser` или аналог на фронтенде.

| Метод | URL | Auth |
|-------|-----|------|
| POST | `/passkeys/login/options` | — |
| POST | `/passkeys/login` | — |
| GET | `/passkeys` | 🔒 |
| POST | `/passkeys/register/options` | 🔒 |
| POST | `/passkeys/register` | 🔒 |
| DELETE | `/passkeys/{id}` | 🔒 |

### Login flow

1. `POST /passkeys/login/options` → options для `navigator.credentials.get()`
2. `POST /passkeys/login` с результатом → `{ token, user }`

### Register flow (для авторизованного пользователя)

1. `POST /passkeys/register/options` → options для `navigator.credentials.create()`
2. `POST /passkeys/register` с attestation → passkey сохранён

## Коды ошибок

| Код | Ситуация |
|-----|----------|
| 401 | Неверный login / нет токена |
| 403 | Email не подтверждён / невалидная verification URL |
| 404 | OAuth-провайдер отключён |
| 422 | Validation errors |

## Route names

Все маршруты имеют префикс имени `fuisic-auth.*` (например `fuisic-auth.login`) для генерации signed URL и тестов.
