<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome! Start enjoying Best of Benefits!</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F3F3F3; font-family: Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #F3F3F3;">
    <tr>
        <td align="center" style="padding: 24px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; background-color: #FFFFFF; border-radius: 8px; overflow: hidden;">
                <tr>
                    <td align="center" style="background-color: #000000; padding: 24px 16px;">
                        <div style="color: #FFD000; font-size: 26px; font-weight: bold; line-height: 1.2;">BEST OF DELRAY BEACH</div>
                        <div style="color: #FFFFFF; font-size: 13px; font-weight: bold; margin-top: 4px;">StoryCreator.Bot</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 32px 32px 24px;">
                        <h1 style="margin: 0 0 20px; color: #000000; font-size: 24px; line-height: 1.3;">Welcome! Start enjoying Best of Benefits!</h1>

                        <p style="margin: 0 0 16px; color: #000000; font-size: 16px; font-weight: bold; line-height: 1.5;">Your enhanced business profile will appear on the Best of Local App within 48 hrs.</p>

                        <p style="margin: 0 0 16px; color: #F26B21; font-size: 16px; font-weight: bold; line-height: 1.5;">Look for Best of Newsletter. Where your feedback and everything you need to know about getting top performance from your social media posts, will be delivered monthly.</p>

                        <p style="margin: 0 0 20px; color: #222222; font-size: 16px; line-height: 1.5;">Your StoryCreator.Bot account, the fastest and easiest way to make social media content, has been launched! Here is the account information and login details to get you started.</p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 24px;">
                            <tr>
                                <td style="background-color: #FEF3EB; border-left: 4px solid #F26B21; border-radius: 4px; padding: 20px 24px;">
                                    <div style="color: #222222; font-size: 15px; font-weight: bold;">Email:</div>
                                    <div style="color: #222222; font-size: 15px; margin: 4px 0 20px;">{{ $email }}</div>
                                    <div style="color: #222222; font-size: 15px; font-weight: bold;">Temporary Password:</div>
                                    <div style="margin-top: 6px;"><span style="display: inline-block; background-color: #FFFFFF; border-radius: 3px; padding: 4px 8px; color: #222222; font-family: 'Courier New', Courier, monospace; font-size: 15px; letter-spacing: 1px;">{{ $password }}</span></div>
                                </td>
                            </tr>
                        </table>

                        <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin: 0 auto 20px;">
                            <tr>
                                <td align="center" style="background-color: #F26B21; border-radius: 6px;">
                                    <a href="{{ $loginUrl }}" style="display: inline-block; padding: 14px 36px; color: #FFFFFF; font-size: 17px; font-weight: bold; text-decoration: none;">Log In Now</a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin: 0 0 12px; color: #555555; font-size: 14px; line-height: 1.5;">This button logs you in automatically. You can also log in anytime with the email and password above.</p>

                        <p style="margin: 0 0 12px; color: #222222; font-size: 16px; line-height: 1.5;">Once you're logged in, you can start creating content stories that you can share to your social media.</p>

                        <p style="margin: 0 0 12px; color: #555555; font-size: 14px; line-height: 1.5;">The password above is temporary. You can reset it anytime from your account dashboard.</p>

                        <p style="margin: 0 0 20px; color: #555555; font-size: 14px; line-height: 1.5;">Go to <a href="{{ $profileUrl }}" style="color: #F26B21; font-weight: bold;">this link</a> to reset your password.</p>

                        <p style="margin: 0; color: #222222; font-size: 15px; line-height: 1.5;">Regards,<br>Best of Delray Beach</p>
                    </td>
                </tr>
                <tr>
                    <td style="background-color: #FAFAFA; border-top: 1px solid #EEEEEE; padding: 16px 32px;">
                        <p style="margin: 0; color: #777777; font-size: 12px; line-height: 1.5;">If you're having trouble clicking the "Log In Now" button, copy and paste the URL below into your web browser:<br>
                            <a href="{{ $loginUrl }}" style="color: #F26B21; word-break: break-all;">{{ $loginUrl }}</a></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
