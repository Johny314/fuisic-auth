<?php

return [

    'middleware' => [],

    'auth_middleware' => ['auth:sanctum'],

    // Лимит для login/register/password/passkey-login (попыток в минуту с одного IP)
    'throttle' => ['throttle:10,1'],

    'user_model' => env('FUISIC_AUTH_USER_MODEL'),

    'token_name' => env('FUISIC_AUTH_TOKEN_NAME', 'api-token'),

    'require_email_verification' => env('FUISIC_AUTH_REQUIRE_EMAIL_VERIFICATION', true),

    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost:8080')),

    'route_prefix' => env('FUISIC_AUTH_ROUTE_PREFIX', ''),

    'login' => [
        // Колонка логина для входа по `login` без email (например `username`); null — вход только по email
        'username_column' => env('FUISIC_AUTH_USERNAME_COLUMN'),
    ],

    'register' => [
        'validation' => [],
        'fillable' => [],
        'defaults' => [],
        // Роли, которые можно выбрать при регистрации (поле `role`); пусто — поле не принимается
        'roles' => [],
        // Роль без выбора и для новых пользователей через OAuth; null — не назначать
        'default_role' => null,
    ],

    'oauth' => [
        'redirect_after_login' => env('FUISIC_AUTH_OAUTH_REDIRECT', null),

        'providers' => [
            'vkontakte' => [
                'enabled' => env('FUISIC_AUTH_VK_ENABLED', false),
                'client_id' => env('VKONTAKTE_CLIENT_ID'),
                'client_secret' => env('VKONTAKTE_CLIENT_SECRET'),
                'redirect' => env('VKONTAKTE_REDIRECT_URI'),
            ],
            'yandex' => [
                'enabled' => env('FUISIC_AUTH_YANDEX_ENABLED', false),
                'client_id' => env('YANDEX_CLIENT_ID'),
                'client_secret' => env('YANDEX_CLIENT_SECRET'),
                'redirect' => env('YANDEX_REDIRECT_URI'),
            ],
        ],
    ],

    'passkeys' => [
        'enabled' => env('FUISIC_AUTH_PASSKEYS_ENABLED', true),
        'relying_party' => [
            'name' => env('FUISIC_AUTH_PASSKEY_RP_NAME', env('APP_NAME', 'FUISIC')),
            'id' => env('FUISIC_AUTH_PASSKEY_RP_ID'),
        ],
    ],

    'queue' => [
        'connection' => env('FUISIC_AUTH_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'rabbitmq')),
        'verification' => env('FUISIC_AUTH_VERIFICATION_QUEUE', 'auth.notifications'),
        'password_reset' => env('FUISIC_AUTH_PASSWORD_RESET_QUEUE', 'auth.notifications'),
    ],

    'verification' => [
        'expire' => 60,
    ],

    'password_reset' => [
        'expire' => 60,
    ],

];
