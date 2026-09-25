# Конфигурация

Файл конфигурации: `config/fuisic-auth.php` (публикуется командой `vendor:publish --tag=fuisic-auth-config`).

## Переменные окружения

| Переменная | По умолчанию | Описание |
|------------|--------------|----------|
| `FUISIC_AUTH_ROUTE_PREFIX` | `''` | Префикс маршрутов (пустой = `/register`, `/login`) |
| `FUISIC_AUTH_USER_MODEL` | — | Eloquent-модель пользователя (fallback: `auth.providers.users.model`) |
| `FUISIC_AUTH_TOKEN_NAME` | `api-token` | Имя Sanctum-токена |
| `FUISIC_AUTH_REQUIRE_EMAIL_VERIFICATION` | `true` | Блокировать login без подтверждённого email |
| `FRONTEND_URL` | `APP_URL` | URL фронтенда для ссылок в письмах |
| `FUISIC_AUTH_QUEUE_CONNECTION` | `QUEUE_CONNECTION` | Очередь для писем |
| `FUISIC_AUTH_VERIFICATION_QUEUE` | `auth.notifications` | Очередь verification |
| `FUISIC_AUTH_PASSWORD_RESET_QUEUE` | `auth.notifications` | Очередь password reset |
| `FUISIC_AUTH_VK_ENABLED` | `false` | Включить OAuth ВКонтакте |
| `FUISIC_AUTH_YANDEX_ENABLED` | `false` | Включить OAuth Яндекс |
| `FUISIC_AUTH_PASSKEYS_ENABLED` | `true` | Включить passkeys |
| `FUISIC_AUTH_PASSKEY_RP_NAME` | `APP_NAME` | Имя relying party |
| `FUISIC_AUTH_PASSKEY_RP_ID` | — | Домен для WebAuthn (например `localhost`) |

### OAuth credentials

| Переменная | Описание |
|------------|----------|
| `VKONTAKTE_CLIENT_ID` | ID приложения VK |
| `VKONTAKTE_CLIENT_SECRET` | Secret VK |
| `VKONTAKTE_REDIRECT_URI` | Callback URL |
| `YANDEX_CLIENT_ID` | ID приложения Yandex |
| `YANDEX_CLIENT_SECRET` | Secret Yandex |
| `YANDEX_REDIRECT_URI` | Callback URL |

Провайдеры OAuth: `vkontakte`, `yandex`.

## RabbitMQ в Laravel

Фрагмент для `config/queue.php`:

```php
'rabbitmq' => [
    'driver' => 'rabbitmq',
    'queue' => env('RABBITMQ_QUEUE', 'default'),
    'connection' => PhpAmqpLib\Connection\AMQPLazyConnection::class,
    'hosts' => [
        [
            'host' => env('RABBITMQ_HOST', '127.0.0.1'),
            'port' => env('RABBITMQ_PORT', 5672),
            'user' => env('RABBITMQ_USER', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'vhost' => env('RABBITMQ_VHOST', '/'),
        ],
    ],
    'options' => ['ssl_options' => []],
    'worker' => env('RABBITMQ_WORKER', 'default'),
    'lazy' => true,
    'after_commit' => false,
],
```

## Почта

Пакет отправляет:

- письмо подтверждения email (шаблон `resources/views/mail/verify-email.blade.php`);
- письмо сброса пароля (шаблон `resources/views/mail/reset-password.blade.php`).

Ссылка из письма подтверждения ведёт на API (`/email/verify/{id}/{hash}`), затем браузер редиректится на `{FRONTEND_URL}/auth/verified?status=ok|already|invalid`.

Настройте `MAIL_*` и `FRONTEND_URL` в `.env`.

Локально в `fuisic-back` поднят **Mailpit**:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@fuisic.local
FRONTEND_URL=http://localhost:8081
```

Письма смотрите в UI: [http://localhost:8025](http://localhost:8025). `MAIL_MAILER=log` ничего в почтовый ящик не кладёт — только в `storage/logs/laravel.log`.

## Passkeys (WebAuthn)

- Реализация — [laravel/passkeys](https://github.com/laravel/passkeys-server) (Actions), маршруты пакета отключены: свои API-маршруты на Sanctum-токенах.
- `FUISIC_AUTH_PASSKEY_RP_ID` должен совпадать с хостом фронта (без порта): для локальной разработки `localhost`.
- Разрешённые origin — `FRONTEND_URL` и `APP_URL` (например `http://localhost:8081`).
- Опции церемоний хранятся в кэше Laravel по challenge и выдаются один раз — сессия браузера не нужна.
- Формат запросов: `POST passkeys/register` — `{name, credential}`, `POST passkeys/login` — `{credential}`; опции приходят как `{options}`.
- На localhost passkeys работают в Chrome/Safari при `RP_ID=localhost`.
- Для Apple Face ID / Touch ID используется стандарт WebAuthn — отдельный Apple OAuth не требуется.

Подробнее про VK: [VK.md](VK.md).

## Middleware

| Ключ config | Значение по умолчанию | Назначение |
|-------------|----------------------|------------|
| `middleware` | `[]` | Middleware группы auth-маршрутов |
| `auth_middleware` | `['auth:sanctum']` | Защищённые эндпоинты |
| `throttle` | `['throttle:10,1']` | Лимит для login, register, password/*, passkeys/login |

## Очереди писем

Jobs:

- `Fuisic\Auth\Jobs\SendVerificationEmailJob`
- `Fuisic\Auth\Jobs\SendPasswordResetEmailJob`

Обе используют connection и queue из `config/fuisic-auth.php` → `queue.*`.

## Миграции пакета

| Таблица | Назначение |
|---------|------------|
| `oauth_accounts` | Привязка VK/Yandex к user |
| `password_reset_tokens` | Токены сброса пароля |
| `passkeys` | Passkeys (laravel/passkeys, `vendor:publish --tag=passkeys-migrations`) |

Sanctum: `personal_access_tokens` — в приложении-хосте.
