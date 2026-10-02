<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class AccountReactivationNotification extends Notification
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

        $firstName = explode(' ', trim($notifiable->name))[0];

        return (new MailMessage)
            ->subject('Your StoryBot account is waiting for you')
            ->greeting("Hi {$firstName},")
            ->line("You created a StoryBot account, but you haven't finished setting it up yet. It only takes a minute.")
            ->line('StoryBot interviews you with a few simple questions and turns your answers into ready-to-post stories for your business. All you have to do is talk, and StoryBot does the writing.')
            ->line('To get started, set your password and log in:')
            ->action('Set Up My Account', $url)
            ->line("Once you're in, you can create your first story in minutes.")
            ->salutation(new HtmlString('Best Regards,<br>The StoryBot Team'));
    }
}
