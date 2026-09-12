<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'FUISIC' }}</title>
</head>
<body style="margin:0;padding:0;background:#0A0E14;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0A0E14;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="max-width:560px;width:100%;">
                    <tr>
                        <td style="padding:24px 8px 20px;">
                            <span style="display:inline-block;width:32px;height:32px;line-height:32px;text-align:center;border-radius:8px;background:#007AFF;color:#ffffff;font-weight:800;font-size:20px;vertical-align:middle;">F</span>
                            <span style="color:#F5F7FA;font-weight:800;letter-spacing:2px;font-size:16px;margin-left:10px;vertical-align:middle;">FUISIC</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#141A22;border:1px solid rgba(255,255,255,0.12);border-radius:24px;padding:32px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 8px 0;color:#9AA8B8;font-size:12px;line-height:18px;">
                            Платформа для изучения физики: карточки, тесты и прогресс.
                            Если вы не запрашивали это письмо, просто проигнорируйте его.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
