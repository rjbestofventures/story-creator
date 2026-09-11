<?php

namespace App\Console\Commands;

use App\Models\Story;
use App\Notifications\EpisodesReactivatedNotification;
use Illuminate\Console\Command;

class NotifyReactivatedEpisodes extends Command
{
    protected $signature = 'trial:notify-reactivations';

    protected $description = 'Tell the team about trial members who brought their episodes back a day ago';

    public function handle(): int
    {
        $due = Story::with('user')
            ->whereNotNull('episodes_reactivated_at')
            ->whereNull('reactivation_notified_at')
            ->where('episodes_reactivated_at', '<=', now()->subHours(Story::REACTIVATION_NOTICE_HOURS))
            ->get();

        foreach ($due as $story) {
            EpisodesReactivatedNotification::sendFor($story);
            $story->forceFill(['reactivation_notified_at' => now()])->save();
        }

        $this->info("Notified {$due->count()} reactivation(s).");

        return self::SUCCESS;
    }
}
