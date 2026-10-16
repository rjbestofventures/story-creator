<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NonVbpSignupNotification extends Notification
{
    public function __construct(private readonly User $user) {}

    public static function sendFor(User $user): void
    {
        NotificationFacade::route('mail', FirstLoginNotification::ADMIN_EMAIL)
            ->notify(new self($user));
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->user->signup_plan ? ucfirst($this->user->signup_plan) : 'Not chosen yet';

        return (new MailMessage)
            ->subject('New Non-VBP sign-up — StoryCreator.Bot')
            ->greeting('New Non-VBP sign-up')
            ->line("{$this->user->name} created an account from the signup page. They have not paid yet.")
            ->line("Email: {$this->user->email}")
            ->line("Phone: {$this->user->phone}")
            ->line("Chose: {$plan}")
            ->salutation('StoryCreator.Bot');
    }
}
