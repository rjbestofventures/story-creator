<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('Verify your email — StoryCreator.Bot')
            ->greeting('Welcome to StoryCreator.Bot!')
            ->line('Please confirm your email address to activate your account and start turning your story into content.')
            ->action('Verify My Email', $url)
            ->line('This link will expire in 7 days. If you did not create this account, no further action is required.')
            ->salutation('The StoryCreator.Bot Team');
    }
}
