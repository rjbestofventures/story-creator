<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class TrialAccountCreatedNotification extends Notification
{
    public function __construct(
        private readonly User $user,
        private readonly string $phone,
    ) {}

    public static function sendFor(User $user, string $phone): void
    {
        NotificationFacade::route('mail', FirstLoginNotification::ADMIN_EMAIL)
            ->notify(new self($user, $phone));
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Complementary Trial account — StoryCreator.Bot')
            ->greeting('New trial signup')
            ->line("{$this->user->name} created a Complementary Trial account from the website.")
            ->line("Email: {$this->user->email}")
            ->line("Phone: {$this->phone}")
            ->salutation('StoryCreator.Bot');
    }
}
