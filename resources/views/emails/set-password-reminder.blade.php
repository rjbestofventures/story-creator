<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StoryCreator.Bot Launch Reminder</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F3F3F3; font-family: Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #F3F3F3;">
    <tr>
        <td align="center" style="padding: 24px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; background-color: #FFFFFF; border-radius: 8px; overflow: hidden;">
                <tr>
                    <td align="center" style="padding: 32px 32px 8px;">
                        <img src="{{ url('images/best-of-delray-beach-logo.png') }}" width="280" alt="Best of Delray Beach" style="display: block; width: 280px; max-width: 100%; height: auto; border: 0;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 24px 32px;">
                        <p style="margin: 0 0 20px; color: #222222; font-size: 18px; line-height: 1.5;">ATTENTION!</p>

                        <p style="margin: 0 0 20px; color: #222222; font-size: 18px; line-height: 1.5;">To our much appreciated and loyal Verified Business Partners,</p>

                        <p style="margin: 0 0 20px; color: #222222; font-size: 18px; line-height: 1.7;">We want you posting!&nbsp; Before you can start creating great posts about your business, you'll need to set your password. We can’t wait to see the exciting outcome of your story.</p>

                        <p style="margin: 0 0 24px; color: #222222; font-size: 18px; line-height: 1.5;">Click below to get going:</p>

                        <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin: 0 auto 32px;">
                            <tr>
                                <td align="center" style="background-color: #F26B21;">
                                    <a href="{{ $url }}" style="display: inline-block; padding: 16px 48px; color: #FFFFFF; font-size: 18px; font-weight: bold; text-decoration: none;">Set Your Password</a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin: 0 0 24px; color: #555555; font-size: 15px; line-height: 1.7;">Once you've set your password, you're in! Log in anytime with your email and start creating stories that bring your business to life on social media.</p>

                        <p style="margin: 0; color: #222222; font-size: 16px; line-height: 1.5;">Regards,<br>Best of Delray Beach</p>
                    </td>
                </tr>
                <tr>
                    <td style="background-color: #FAFAFA; border-top: 1px solid #EEEEEE; padding: 16px 32px;">
                        <p style="margin: 0; color: #777777; font-size: 12px; line-height: 1.5;">If you're having trouble clicking the "Set Your Password" button, copy and paste the URL below into your web browser:<br>
                            <a href="{{ $url }}" style="color: #F26B21; word-break: break-all;">{{ $url }}</a></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
