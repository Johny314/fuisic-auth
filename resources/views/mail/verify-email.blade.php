@php
    $title = 'Подтверждение email';
@endphp
@component('fuisic-auth::mail.layout', ['title' => $title])
    <p style="margin:0 0 8px;color:#9AA8B8;font-size:13px;letter-spacing:1.4px;text-transform:uppercase;">Регистрация</p>
    <h1 style="margin:0 0 16px;color:#F5F7FA;font-size:28px;line-height:1.2;">Подтвердите email</h1>
    <p style="margin:0 0 16px;color:#F5F7FA;font-size:16px;line-height:1.5;">
        Здравствуйте{{ !empty($name) ? ', '.$name : '' }}. Чтобы завершить регистрацию в FUISIC и войти в аккаунт, подтвердите адрес почты.
    </p>
    <p style="margin:0 0 24px;color:#9AA8B8;font-size:15px;line-height:1.5;">
        {{ $intro }}
    </p>
    <p style="margin:0 0 28px;">
        <a href="{{ $url }}" style="display:inline-block;background:#007AFF;color:#ffffff;text-decoration:none;font-weight:700;padding:14px 22px;border-radius:14px;">
            {{ $action }}
        </a>
    </p>
    <p style="margin:0;color:#9AA8B8;font-size:13px;line-height:1.5;">
        Ссылка действительна {{ $expire }} минут. Если кнопка не открывается, скопируйте адрес:<br>
        <a href="{{ $url }}" style="color:#4DA3FF;word-break:break-all;">{{ $url }}</a>
    </p>
@endcomponent
