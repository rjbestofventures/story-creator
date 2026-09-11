<?php

namespace App\Notifications;

use App\Models\SiteSetting;
use App\Models\Story;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class EpisodesReactivatedNotification extends Notification
{
    /** Used until an admin sets a recipient under Settings → Features. */
    public const FALLBACK_EMAIL = 'dickstein@bestofventures.com';

    public function __construct(private readonly Story $story) {}

    public static function sendFor(Story $story): void
    {
        $recipient = SiteSetting::get('admin_notification_email', self::FALLBACK_EMAIL);

        NotificationFacade::route('mail', $recipient)->notify(new self($story));
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->story->user?->name ?? 'Unknown';

        return (new MailMessage)
            ->subject('Trial member reactivated their episodes — StoryCreator.Bot')
            ->greeting('Hi Team,')
            ->line("This User \"{$name}\" reactivate their episodes. Please reach out accordingly")
            ->salutation('StoryCreator.Bot');
    }
}
