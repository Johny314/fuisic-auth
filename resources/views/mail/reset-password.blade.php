@php
    $title = 'Сброс пароля';
@endphp
@component('fuisic-auth::mail.layout', ['title' => $title])
    <p style="margin:0 0 8px;color:#9AA8B8;font-size:13px;letter-spacing:1.4px;text-transform:uppercase;">Безопасность</p>
    <h1 style="margin:0 0 16px;color:#F5F7FA;font-size:28px;line-height:1.2;">Сброс пароля</h1>
    <p style="margin:0 0 24px;color:#F5F7FA;font-size:16px;line-height:1.5;">
        Вы запросили новый пароль для аккаунта FUISIC. Нажмите кнопку, чтобы задать его.
    </p>
    <p style="margin:0 0 28px;">
        <a href="{{ $url }}" style="display:inline-block;background:#007AFF;color:#ffffff;text-decoration:none;font-weight:700;padding:14px 22px;border-radius:14px;">
            {{ $action }}
        </a>
    </p>
    <p style="margin:0;color:#9AA8B8;font-size:13px;line-height:1.5;">
        Ссылка действительна {{ $expire }} минут. Если вы не запрашивали сброс, ничего не делайте.
    </p>
@endcomponent
