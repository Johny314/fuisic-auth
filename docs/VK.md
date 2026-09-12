# Авторизация через ВКонтакте

Код уже есть в пакете `fuisic-laravel-auth`. Чтобы кнопка **VK** на экране входа заработала, нужно приложение VK и несколько переменных в `.env` бэкенда. Без этого API отвечает «OAuth-провайдер отключён».

## Что уже сделано в коде

- Socialite + `socialiteproviders/vkontakte` (OAuth на `oauth.vk.ru`, scope `email`).
- Маршруты:
  - `GET /oauth/vkontakte/redirect` — JSON `{ "url": "..." }` для фронта;
  - `GET /oauth/vkontakte/callback` — VK редиректит сюда; браузер уходит на фронт с токеном;
  - `GET /oauth/vkontakte/link` — привязка к текущему пользователю (нужен Bearer);
  - `GET /oauth/linked` / `DELETE /oauth/vkontakte`.
- Credentials читаются из `config/services.php` (`VKONTAKTE_*`).
- Флаг включения: `FUISIC_AUTH_VK_ENABLED`.
- После логина браузер открывает `FRONTEND_URL/auth/oauth-callback?token=...`.
- Если пользователь уже вошёл и шёл через `/link`, редирект на `/profile/edit?oauth=linked&provider=vkontakte`.
- Ошибка: `/auth/auth?oauth_error=...`.

JSON-ответ callback отдаётся только если клиент явно просит JSON (`Accept: application/json`) — это для тестов, не для браузера VK.

## Чего не хватает (локально)

| Что | Сейчас | Нужно |
|-----|--------|--------|
| `FUISIC_AUTH_VK_ENABLED` | `false` / нет в `.env` | `true` |
| `VKONTAKTE_CLIENT_ID` | пусто | ID приложения VK |
| `VKONTAKTE_CLIENT_SECRET` | пусто | защищённый ключ |
| `VKONTAKTE_REDIRECT_URI` | желательно задать явно | `http://localhost:8080/oauth/vkontakte/callback` |
| Приложение в VK | нет | сайт / веб, с тем же redirect URI |
| Email | VK может не отдать | включить доступ к email в настройках приложения |

Секреты в чат не кладите — только в `.env` бэкенда.

## 1. Создать приложение VK

1. Откройте [id.vk.ru/about/business/go/docs](https://id.vk.ru/about/business/go/docs) или кабинет [dev.vk.com](https://dev.vk.com).
2. Создайте приложение типа **Веб-сайт** / **VK ID** (не standalone-мобильное).
3. В настройках укажите:
   - доверенный redirect URI: `http://localhost:8080/oauth/vkontakte/callback`
   - базовый домен: `localhost` (если кабинет требует домен — для продакшена свой хост)
4. Включите доступ к **email** (scope `email`). Без email вход всё равно возможен: поле `users.email` nullable, но потом нельзя войти по паролю, пока email не задан.
5. Скопируйте **ID приложения** и **защищённый ключ**.

Для продакшена добавьте туда же `https://<ваш-api-домен>/oauth/vkontakte/callback`. Локальный и боевой URI — разные приложения или оба URI в одном приложении, если VK это позволяет.

## 2. Прописать `.env` бэкенда

```env
FUISIC_AUTH_VK_ENABLED=true
FRONTEND_URL=http://localhost:8081

VKONTAKTE_CLIENT_ID=1234567
VKONTAKTE_CLIENT_SECRET=put-secret-here
VKONTAKTE_REDIRECT_URI=http://localhost:8080/oauth/vkontakte/callback
```

`VKONTAKTE_REDIRECT_URI` **должен совпадать символ в символ** с URI в кабинете VK (схема, хост, порт, путь, без `/` на конце).

Перезапустите PHP-FPM / контейнер `app`, чтобы подтянуть env:

```bash
cd fuisic_back
docker compose exec app php artisan config:clear
```

## 3. Проверить

```bash
curl -s http://localhost:8080/oauth/vkontakte/redirect
```

Ожидается JSON с `url` на `https://oauth.vk.ru/authorize?...`. Если `404` и текст про отключённый провайдер — флаг `FUISIC_AUTH_VK_ENABLED` не подхватился.

Дальше:

1. Откройте фронт `http://localhost:8081/auth/auth`.
2. Нажмите **VK** — должен открыться VK, затем вернуться на сайт уже авторизованным.
3. Если VK показывает «redirect_uri is incorrect» — URI в кабинете и в `.env` различаются.
4. Если после VK видите JSON с `token` вместо сайта — callback открыли как API (например из curl), а не из браузера.

## 4. Привязка к существующему аккаунту

Авторизованный пользователь:

```
GET /oauth/vkontakte/link
Authorization: Bearer <token>
```

Фронт открывает `url` из ответа. После VK редирект в профиль. Отвязка: `DELETE /oauth/vkontakte`.

Нельзя отвязать последний способ входа, если у пользователя нет пароля.

## Типичные ошибки

| Симптом | Причина |
|---------|---------|
| Кнопка VK: «OAuth недоступен» | `FUISIC_AUTH_VK_ENABLED=false` или нет credentials |
| VK: redirect_uri неверный | URI в кабинете ≠ `VKONTAKTE_REDIRECT_URI` |
| Вошли, но нет email | В приложении VK не выдан scope email |
| Аккаунт уже привязан | Этот VK ID связан с другим user |
| CORS / cookie | Для OAuth не нужны: обмен идёт редиректом браузера, токен в query |

## Яндекс (по тому же шаблону)

```env
FUISIC_AUTH_YANDEX_ENABLED=true
YANDEX_CLIENT_ID=
YANDEX_CLIENT_SECRET=
YANDEX_REDIRECT_URI="${APP_URL}/oauth/yandex/callback"
```

Callback: `http://localhost:8080/oauth/yandex/callback`.
