<?php

namespace App\Notifications;

use App\Models\CreditPack;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class RetailPackPurchasedNotification extends Notification
{
    public function __construct(
        private readonly User $user,
        private readonly CreditPack $pack,
        private readonly int $amountPaid,
    ) {}

    public static function sendFor(User $user, CreditPack $pack, int $amountPaid): void
    {
        NotificationFacade::route('mail', FirstLoginNotification::ADMIN_EMAIL)
            ->notify(new self($user, $pack, $amountPaid));
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = '$'.number_format($this->amountPaid / 100, 2);

        return (new MailMessage)
            ->subject("{$this->user->name} bought {$this->pack->label} — StoryCreator.Bot")
            ->greeting('New Retail Pack purchase')
            ->line("{$this->user->name} bought {$this->pack->label} ({$amount}).")
            ->line("Email: {$this->user->email}")
            ->line('Phone: '.($this->user->phone ?: '—'))
            ->salutation('StoryCreator.Bot');
    }
}
