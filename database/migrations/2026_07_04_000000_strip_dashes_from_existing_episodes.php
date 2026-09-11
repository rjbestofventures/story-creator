<?php

use App\Models\Episode;
use App\Models\EpisodeVersion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Episode::cursor() as $episode) {
            $title = Episode::stripDashes((string) $episode->title);
            $content = Episode::stripDashes((string) $episode->content);

            if ($title !== $episode->title || $content !== $episode->content) {
                $episode->newQuery()->whereKey($episode->getKey())->update([
                    'title' => $title,
                    'content' => $content,
                ]);
            }
        }

        foreach (EpisodeVersion::cursor() as $version) {
            $title = Episode::stripDashes((string) $version->title);
            $content = Episode::stripDashes((string) $version->content);

            if ($title !== $version->title || $content !== $version->content) {
                $version->newQuery()->whereKey($version->getKey())->update([
                    'title' => $title,
                    'content' => $content,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Dashes cannot be reliably restored; nothing to reverse.
    }
};
