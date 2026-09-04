<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password Notice</title>
</head>
<body style="margin:0; padding:0; background:#f1f4f9; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f4f9; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0"
                       style="background:#ffffff; border-radius:10px; overflow:hidden; max-width:480px; width:100%;">
                    <tr>
                        <td style="background:#14304f; padding:28px 24px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:20px;">Change Password Notice</h1>
                            <p style="margin:6px 0 0; color:#c9d6e8; font-size:13px;">
                                {{ config('app.name') }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 24px; color:#1f2937; font-size:14px; line-height:1.6;">
                            <p style="margin-top:0;">Hello <strong>{{ $name }}</strong>,</p>

                            <p>
                                You have requested to change your password for your account.
                                Please click the button below to open your Change Password Form.
                            </p>

                            <p style="text-align:center; margin:28px 0;">
                                <a href="{{ $resetUrl }}"
                                   style="background:#14304f; color:#ffffff; text-decoration:none;
                                          padding:12px 28px; border-radius:6px; font-weight:bold;
                                          display:inline-block;">
                                    Change Password Form
                                </a>
                            </p>

                            <p style="color:#b91c1c; font-size:13px;">
                                <strong>Important:</strong> The link will be valid only until
                                {{ $expiresAt }}. It will also expire once you have successfully
                                changed your password. Do not share this link with anyone to
                                protect your account.
                            </p>

                            <p style="font-size:13px; color:#6b7280; margin-bottom:0;">
                                If you did not request to change your password, you can safely
                                ignore this email &mdash; your password will remain unchanged.
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="font-size:12px; color:#9ca3af; margin-top:16px;">
                    {{ config('app.name') }} &copy; {{ date('Y') }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
