# Установка

## Composer

### Из GitHub (production)

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Johny314/fuisic-auth"
        }
    ],
    "require": {
        "fuisic/auth": "^1.0"
    }
}
```

```bash
composer update fuisic/auth
```

### Локальная разработка (path repository)

Если репозиторий пакета лежит **рядом** с приложением:

```
FUISIC/
├── fuisic-back/
└── fuisic-auth/
```

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../fuisic-auth",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "fuisic/auth": "@dev"
    }
}
```

В Docker смонтируйте соседний каталог (как в `fuisic-back/docker-compose-local.yml`):

```yaml
volumes:
  - '.:/var/www/html'
  - '../fuisic-auth:/var/www/fuisic-auth:ro'
```

## Laravel

Пакет регистрируется автоматически через `FuisicAuthServiceProvider`.

### 1. Публикация конфигурации и миграций

```bash
php artisan vendor:publish --tag=fuisic-auth-config
php artisan vendor:publish --tag=fuisic-auth-migrations
php artisan vendor:publish --tag=passkeys-migrations
php artisan migrate
```

### 2. Модель User

```php
use Fuisic\Auth\Traits\HasFuisicAuth;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    use HasApiTokens, HasFuisicAuth;

    protected $fillable = ['name', 'email', 'password'];
}
```

### 3. Sanctum

Убедитесь, что Sanctum установлен и миграция `personal_access_tokens` выполнена:

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 4. Очередь RabbitMQ

В `.env` приложения:

```env
QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
```

Добавьте connection `rabbitmq` в `config/queue.php` (см. [CONFIGURATION.md](CONFIGURATION.md)).

Запустите worker:

```bash
php artisan queue:work rabbitmq --queue=auth.notifications,default
```

### 5. OAuth (опционально)

Пошаговая инструкция для ВКонтакте: [VK.md](VK.md).

Добавьте credentials в `config/services.php`:

```php
'vkontakte' => [
    'client_id' => env('VKONTAKTE_CLIENT_ID'),
    'client_secret' => env('VKONTAKTE_CLIENT_SECRET'),
    'redirect' => env('VKONTAKTE_REDIRECT_URI'),
],
'yandex' => [
    'client_id' => env('YANDEX_CLIENT_ID'),
    'client_secret' => env('YANDEX_CLIENT_SECRET'),
    'redirect' => env('YANDEX_REDIRECT_URI'),
],
```

Включите провайдеры в `.env`:

```env
FUISIC_AUTH_VK_ENABLED=true
FUISIC_AUTH_YANDEX_ENABLED=true
```

### 6. Дополнительные поля при регистрации

В опубликованном `config/fuisic-auth.php`:

```php
'register' => [
    'validation' => [
        'role' => ['sometimes', 'string'],
    ],
    'fillable' => ['role'],
    'defaults' => [
        'role' => 'user',
    ],
],
```

### 7. User model override (опционально)

```env
FUISIC_AUTH_USER_MODEL=App\Models\User
```

По умолчанию используется `config('auth.providers.users.model')`.

## Проверка

```bash
php artisan route:list --path=register
php artisan route:list --path=oauth
```

Тест регистрации:

```bash
curl -X POST http://localhost:8080/register \
  -H 'Content-Type: application/json' \
  -d '{"name":"Test","email":"test@example.com","password":"secret123","password_confirmation":"secret123"}'
```

Ожидается ответ с просьбой подтвердить email.
