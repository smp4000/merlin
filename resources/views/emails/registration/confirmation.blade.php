<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ __('registration.mail.subject') }}</title>
    <style>
        @media only screen and (max-width: 640px) {
            .merlin-shell { width: 100% !important; }
            .merlin-card { padding: 30px 22px !important; }
            .merlin-header { padding: 24px 22px !important; }
            .merlin-title { font-size: 28px !important; line-height: 34px !important; }
            .merlin-button { display: block !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#f1f6f5; color:#122326; font-family:'Segoe UI',Arial,sans-serif; -webkit-text-size-adjust:100%;">
    {{-- Vorschautext ohne Token oder Personendaten für die Inbox-Ansicht. --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">
        {{ __('registration.mail.preheader', ['minutes' => $lifetimeMinutes]) }}
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; background:#f1f6f5;">
        <tr>
            <td align="center" style="padding:38px 14px;">
                <table role="presentation" width="620" cellspacing="0" cellpadding="0" border="0" class="merlin-shell" style="width:620px; max-width:620px;">
                    <tr>
                        <td class="merlin-header" style="padding:28px 34px; background:#122326; border-radius:22px 22px 0 0; border-bottom:4px solid #ffb400;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="52" valign="middle">
                                        <div style="width:44px; height:44px; line-height:44px; border-radius:13px; background:#087f78; color:#ffffff; text-align:center; font-size:20px; font-weight:800;">M</div>
                                    </td>
                                    <td valign="middle" style="padding-left:12px;">
                                        <div style="color:#ffffff; font-size:21px; line-height:24px; font-weight:800; letter-spacing:-0.3px;">Merlin</div>
                                        <div style="padding-top:3px; color:#a8c2bf; font-size:10px; line-height:14px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase;">{{ __('registration.mail.brand_subtitle') }}</div>
                                    </td>
                                    <td align="right" valign="middle" style="color:#dff5ef; font-size:12px; font-weight:700;">{{ __('registration.mail.security_label') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="merlin-card" style="padding:46px 48px 42px; background:#ffffff; border:1px solid #dce7e5; border-top:0; border-radius:0 0 22px 22px; box-shadow:0 18px 50px rgba(18,35,38,0.10);">
                            <div style="color:#087f78; font-size:11px; line-height:16px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase;">{{ __('registration.mail.eyebrow') }}</div>
                            <h1 class="merlin-title" style="margin:10px 0 0; color:#122326; font-size:36px; line-height:42px; font-weight:800; letter-spacing:-1px;">{{ __('registration.mail.title') }}</h1>
                            <p style="margin:18px 0 0; color:#526a6d; font-size:16px; line-height:27px;">{{ __('registration.mail.introduction') }}</p>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:30px 0 28px;">
                                <tr><td style="border-radius:12px; background:#087f78;">
                                    <a href="{{ $actionUrl }}" class="merlin-button" style="display:inline-block; padding:15px 24px; border-radius:12px; color:#ffffff; background:#087f78; font-size:15px; line-height:20px; font-weight:800; text-decoration:none;">{{ __('registration.mail.action') }} &nbsp;→</a>
                                </td></tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; background:#eaf7f3; border-left:4px solid #087f78; border-radius:12px;">
                                <tr><td style="padding:17px 18px;">
                                    <div style="color:#075e5a; font-size:14px; line-height:20px; font-weight:800;">{{ __('registration.mail.expiry_title') }}</div>
                                    <div style="padding-top:4px; color:#526a6d; font-size:13px; line-height:21px;">{{ __('registration.mail.expiry', ['minutes' => $lifetimeMinutes]) }}</div>
                                </td></tr>
                            </table>

                            <div style="margin-top:28px; padding-top:24px; border-top:1px solid #dce7e5;">
                                <div style="color:#122326; font-size:14px; line-height:21px; font-weight:800;">{{ __('registration.mail.not_requested_title') }}</div>
                                <p style="margin:5px 0 0; color:#617477; font-size:13px; line-height:21px;">{{ __('registration.mail.ignore') }}</p>
                            </div>

                            <p style="margin:28px 0 0; color:#122326; font-size:14px; line-height:23px;">{{ __('registration.mail.closing') }}<br><strong>{{ __('registration.mail.team') }}</strong></p>

                            <div style="margin-top:26px; padding:18px; border-radius:12px; background:#f5f8f7; color:#718184; font-size:11px; line-height:18px; word-break:break-word;">
                                {{ __('registration.mail.fallback') }}<br>
                                <a href="{{ $actionUrl }}" style="color:#075e5a; text-decoration:underline;">{{ $actionUrl }}</a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:24px 20px 0; color:#829294; font-size:11px; line-height:18px;">
                            {{ __('registration.mail.footer') }}<br>© {{ now()->year }} Merlin
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
