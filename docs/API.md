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
  "password_confirmation": "secret123",
  "role": "teacher"
}
```

`role` — необязательно, одно из `register.roles` в конфиге (без него — `register.default_role`). Если `register.roles` пуст, поле не принимается.

**201** — регистрация успешна, отправлено письмо:

```json
{ "message": "Регистрация успешна. Подтвердите email — письмо отправлено." }
```

Дополнительные поля модели настраиваются в `config/fuisic-auth.php` → `register`.

---

### POST `/login`

```json
{
  "login": "ivan@example.com",
  "password": "secret123"
}
```

`login` — email или логин (если в конфиге задан `login.username_column`, см. [CONFIGURATION.md](CONFIGURATION.md#вход-по-логину)); логин сравнивается без учёта регистра. Старое поле `email` вместо `login` продолжает работать. Нужно одно из двух полей.

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

У пользователя без email (вход по логину) `email` и `email_verified_at` — `null`.

**401** — неверный логин/email или пароль. **403** — вход по email, а email не подтверждён (вход по логину подтверждения не требует). **422** — нет ни `login`, ни `email`; при `login.username_column = null` `login` должен быть email.

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

Дополнительные поля (например `roles`, `permissions`) добавляет модель пользователя методом `authProfile()`.

## Email verification

### GET `/email/verify/{id}/{hash}`

Signed URL из письма. Подтверждает email без авторизации.

### POST `/email/verify/resend` 🔒

Повторная отправка письма. **422** — у пользователя нет email.

## Password

### POST `/password/forgot`

```json
{ "email": "ivan@example.com" }
```

Только по email: пользователю без email пароль сбрасывает приложение.

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

Сервер — [laravel/passkeys](https://github.com/laravel/passkeys-server); на фронте достаточно `navigator.credentials` (base64url ↔ ArrayBuffer), см. `fuisic-front/utils/passkeys.ts`.

| Метод | URL | Auth |
|-------|-----|------|
| POST | `/passkeys/login/options` | — (throttle) |
| POST | `/passkeys/login` | — (throttle) |
| GET | `/passkeys` | 🔒 |
| POST | `/passkeys/register/options` | 🔒 |
| POST | `/passkeys/register` | 🔒 |
| DELETE | `/passkeys/{id}` | 🔒 |

### Login flow

1. `POST /passkeys/login/options` → `{ options }` для `navigator.credentials.get({ publicKey: options })`
2. `POST /passkeys/login` с `{ credential }` → `{ token, user }`

### Register flow (для авторизованного пользователя)

1. `POST /passkeys/register/options` → `{ options }` для `navigator.credentials.create({ publicKey: options })`
2. `POST /passkeys/register` с `{ name, credential }` → `201`, passkey сохранён

`credential` — сериализованный `PublicKeyCredential`: `{ id, rawId, type: "public-key", response: {...} }` (бинарные поля в base64url). Опции одноразовые и живут `timeout` (60 с); повторная или просроченная попытка → `422` с ошибкой в поле `credential`.

`GET /passkeys` → `{ passkeys: [{ id, name, last_used_at, created_at }] }`.

## Коды ошибок

| Код | Ситуация |
|-----|----------|
| 401 | Неверный login / нет токена |
| 403 | Вход по неподтверждённому email / невалидная verification URL |
| 404 | OAuth-провайдер отключён |
| 422 | Validation errors (в т.ч. невалидный/просроченный passkey) |
| 429 | Слишком много попыток входа/регистрации (`throttle`) |

## Route names

Все маршруты имеют префикс имени `fuisic-auth.*` (например `fuisic-auth.login`) для генерации signed URL и тестов.
