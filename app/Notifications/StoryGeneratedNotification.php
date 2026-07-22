<?php

namespace App\Notifications;

use App\Models\Story;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class StoryGeneratedNotification extends Notification
{
    private const ADMIN_EMAIL = 'dickstein@bestofventures.com';

    public function __construct(private readonly Story $story) {}

    public static function sendFor(Story $story): void
    {
        NotificationFacade::route('mail', self::ADMIN_EMAIL)->notify(new self($story));
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $user = $this->story->user;

        return (new MailMessage)
            ->subject('Story generated — StoryCreator.Bot')
            ->greeting('New story generated')
            ->line("{$user->name} ({$user->email}) generated the story \"{$this->story->title}\" ({$this->story->episodes()->count()} episodes).")
            ->salutation('StoryCreator.Bot');
    }
}
