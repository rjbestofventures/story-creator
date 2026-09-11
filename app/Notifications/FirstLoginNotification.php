<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class FirstLoginNotification extends Notification
{
    private const ADMIN_EMAIL = 'dickstein@bestofventures.com';

    public function __construct(private readonly User $user) {}

    public static function sendFor(User $user): void
    {
        NotificationFacade::route('mail', self::ADMIN_EMAIL)->notify(new self($user));
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New first-time login — StoryCreator.Bot')
            ->greeting('New user activity')
            ->line("{$this->user->name} ({$this->user->email}) just logged in for the first time.")
            ->salutation('StoryCreator.Bot');
    }
}
