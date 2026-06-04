<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreatedNotification extends Notification
{
    public function __construct(private readonly string $token) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Your StoryCreator.Bot account is ready')
            ->greeting("Hi {$notifiable->name},")
            ->line('An account has been created for you on StoryCreator.Bot.')
            ->line('Click the button below to set your password and get started.')
            ->action('Set My Password', $url)
            ->line('This link will expire in 60 minutes. If you need a new one, visit the login page and use "Forgot Password".')
            ->salutation('The StoryCreator.Bot Team');
    }
}
