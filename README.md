# fuisic/auth

[![CI](https://github.com/Johny314/fuisic-auth/actions/workflows/ci.yml/badge.svg)](https://github.com/Johny314/fuisic-auth/actions/workflows/ci.yml) [![Release](https://img.shields.io/github/v/release/Johny314/fuisic-auth)](https://github.com/Johny314/fuisic-auth/releases)

Laravel-пакет авторизации для API-проектов FUISIC: Sanctum-токены, подтверждение email через RabbitMQ, OAuth (ВКонтакте, Яндекс), passkeys (WebAuthn) и сброс пароля.

## Возможности

- Регистрация по email, вход по email или логину и паролю (Laravel Sanctum)
- Подтверждение email с асинхронной отправкой писем (RabbitMQ)
- Сброс и смена пароля
- OAuth: вход и привязка аккаунтов ВК и Яндекс
- Passkeys (Face ID / Touch ID на Apple и аналоги)
- Привязка нескольких OAuth-провайдеров к одному пользователю

## Требования

- PHP 8.4+
- Laravel 13+
- PostgreSQL / MySQL (любая БД Laravel)
- RabbitMQ (для очередей писем)
- Redis (рекомендуется для кэша)

## Быстрый старт

```bash
composer require fuisic/auth
php artisan vendor:publish --tag=fuisic-auth-config
php artisan vendor:publish --tag=fuisic-auth-migrations
php artisan vendor:publish --tag=passkeys-migrations
php artisan migrate
```

Подробнее: [docs/INSTALLATION.md](docs/INSTALLATION.md)

## Документация

| Файл | Описание |
|------|----------|
| [docs/INSTALLATION.md](docs/INSTALLATION.md) | Установка и подключение к проекту |
| [docs/CONFIGURATION.md](docs/CONFIGURATION.md) | Переменные окружения и config |
| [docs/API.md](docs/API.md) | HTTP-эндпоинты и примеры запросов |
| [docs/VK.md](docs/VK.md) | Настройка OAuth ВКонтакте |

## Модель пользователя

```php
use Fuisic\Auth\Traits\HasFuisicAuth;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    use HasApiTokens, HasFuisicAuth;
}
```

## Используется в

- [fuisic-back](https://github.com/Johny314/fuisic-back) — backend FUISIC

## Лицензия

MIT. См. [LICENSE](LICENSE).
