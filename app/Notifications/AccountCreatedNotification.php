<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreatedNotification extends Notification
{
    public function __construct(private readonly string $password) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your StoryCreator.Bot account is ready')
            ->view('emails.account-created', [
                'email' => $notifiable->email,
                'password' => $this->password,
                'loginUrl' => $notifiable->loginLink('stories'),
                'profileUrl' => $notifiable->loginLink('profile'),
            ]);
    }
}
