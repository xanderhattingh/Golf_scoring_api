<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset your password</title>
</head>
<body style="margin:0;padding:0;background:#0a1a0d;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#f4efe2;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0a1a0d;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:520px;background:#123415;border:1px solid rgba(212,175,55,0.35);border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:36px 32px 20px 32px;text-align:center;background:linear-gradient(180deg,#1c4a20 0%,#123415 100%);border-bottom:1px solid rgba(212,175,55,0.3);">
                            <div style="font-family:Georgia,'Times New Roman',serif;font-size:28px;font-weight:700;color:#f4efe2;letter-spacing:0.5px;margin:0;">
                                Golf <span style="color:#d4af37;font-style:italic;">Scoring</span>
                            </div>
                            <div style="font-size:11px;letter-spacing:3px;color:#d4af37;text-transform:uppercase;margin-top:6px;">
                                Password Reset
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px 0;font-size:16px;line-height:1.5;color:#f4efe2;">
                                Hi {{ $firstName }},
                            </p>
                            <p style="margin:0 0 20px 0;font-size:15px;line-height:1.6;color:#e5dbc4;">
                                We received a request to reset the password on your Golf Scoring account. Tap the button below to choose a new one — it'll open the app.
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:28px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $deepLink }}"
                                           style="display:inline-block;background:linear-gradient(135deg,#f4d489 0%,#d4af37 55%,#b8960c 100%);color:#0a1a0d;text-decoration:none;padding:14px 34px;border-radius:8px;font-size:15px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;box-shadow:0 4px 12px rgba(0,0,0,0.4);">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 8px 0;font-size:13px;line-height:1.6;color:#a89f8a;">
                                This link expires in {{ $expiresInMinutes }} minutes. If you didn't ask for a password reset, you can ignore this email — your password won't change.
                            </p>
                            <p style="margin:24px 0 0 0;font-size:12px;line-height:1.6;color:#7a725f;">
                                If the button doesn't work, copy and paste this into your device:<br>
                                <span style="color:#d4af37;word-break:break-all;">{{ $deepLink }}</span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px;text-align:center;background:#0f2812;border-top:1px solid rgba(212,175,55,0.2);">
                            <p style="margin:0;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#7a725f;">
                                Golf Scoring · Track · Compete · Improve
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
