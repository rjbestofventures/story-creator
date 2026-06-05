<?php

use App\Models\Episode;
use App\Models\Story;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $episodes = [
            [
                'episode_number' => 1,
                'title' => 'The Thursday That Changed Everything',
                'content' => "Eleven years. That is how long I worked for someone else.\n\nI started as a crew member — hauling mulch, pulling weeds, learning every inch of how this work gets done. By year eleven I was managing six crews across three commercial accounts. I knew the trade inside out.\n\nI also knew exactly how corners were getting cut.\n\nThe breaking point came on a big install for a corporate office park. Beautiful design, solid budget. My boss told me to swap the specified irrigation system for a cheaper one — without telling the client.\n\nI refused. He said do it or find somewhere else to work.\n\nI drove home. Called my wife Sandra. Said I think I just quit.\n\nShe said: good.\n\nThat was a Thursday. By Monday I had a business name and a trailer.\n\nSome decisions take eleven years to make. And about four days to act on.\n\nWhat standard have you been compromising on that you know, deep down, you need to stop?",
                'format' => 'social',
            ],
            [
                'episode_number' => 2,
                'title' => 'What My Uncle Ray Taught Me',
                'content' => "My uncle Ray had a small landscaping operation in San Antonio. Nothing fancy — just residential maintenance. I spent summers with him when I was twelve, thirteen years old.\n\nHe was obsessive about doing things right. He would re-edge a lawn three times if it wasn't clean.\n\nI thought he was crazy.\n\nThen one day he said something I never forgot:\n\n*The yard is the first thing people see when they come home. You're responsible for how someone feels when they pull into their driveway.*\n\nI was thirteen. I didn't fully understand it yet. But it stuck.\n\nRay passed in 2019. I still hear his voice on every job — especially when I'm tempted to rush something. He would not be impressed by shortcuts.\n\nHe gave me something most people in this industry never get: a standard. Not just how to do the work, but why it matters.\n\nEverything I've built at Torres Landscaping traces back to that driveway.\n\nWho gave you the standard you hold yourself to?",
                'format' => 'social',
            ],
            [
                'episode_number' => 3,
                'title' => 'Why I Call Every Client After Every Job',
                'content' => "Most contractors wait for complaints.\n\nI go looking for them.\n\nAfter every major job, I call every client personally — not a text, a call. I ask them to walk the property and tell me honestly what they think. If something isn't right, I come back and fix it before the invoice goes out.\n\nI'd rather hear it from my client than see it in a Google review.\n\nAt the company I came from, nobody knew the client's name. Nobody knew what mattered to them. We showed up, did the job, moved on.\n\nI swore Torres Landscaping would never feel like that.\n\nEvery client I have, I know their dog's name. I know which neighbor they don't get along with. I know if they're going through something hard.\n\nThat's not a system. That's just paying attention.\n\nWhen did you last ask a client — honestly — if everything was right?",
                'format' => 'social',
            ],
            [
                'episode_number' => 4,
                'title' => 'The Client Who Almost Sold Her House',
                'content' => "She called me because she couldn't keep up with the property anymore.\n\nA widow in her seventies. Big lot. Established trees. A rose garden her late husband had planted and tended for forty years.\n\nShe was thinking about selling the house. She was embarrassed about the state of it.\n\nWe spent two days getting the property back — clearing what needed clearing, restoring what could be restored, protecting what her husband had built.\n\nWhen I called her after, she was crying.\n\nShe said she felt like her husband was still there.\n\nShe has been a client for four years now. I don't think she's selling that house.\n\nThat job had nothing to do with landscaping, really. It was about someone getting to stay home.\n\nThe work we do matters more than we sometimes realize. Show up like it does.\n\nIs there a client whose story you carry with you?",
                'format' => 'social',
            ],
            [
                'episode_number' => 5,
                'title' => 'Eight Families Are Counting on Me',
                'content' => "When I started Torres Landscaping, I thought the hardest part would be finding clients.\n\nI had no idea the hardest part would be being a boss.\n\nI have eight full-time employees. Some of them have families depending on a paycheck. When a big account goes quiet or a contract doesn't renew, my first thought isn't about me. It's about them.\n\nThat weight — I didn't expect it. Nobody prepares you for it.\n\nBut it also changes how you show up. Every decision I make, I think about the ripple.\n\nWe also do pro bono maintenance for two group homes in our area that house adults with disabilities. My crew member Miguel suggested it three years ago. I said yes. We go out once a month. Some of the residents come outside and watch. A few help with small things.\n\nIt has become one of my favorite days of the month.\n\nA crew member suggested it and I said yes. That is the kind of culture I want to build.\n\nWhat does your business do — that has nothing to do with revenue — that you're quietly proud of?",
                'format' => 'social',
            ],
        ];

        Story::where('is_demo', true)
            ->where('status', 'interview_complete')
            ->whereDoesntHave('episodes')
            ->each(function (Story $story) use ($episodes) {
                foreach ($episodes as $ep) {
                    Episode::create(array_merge($ep, ['story_id' => $story->id]));
                }

                $story->update(['title' => 'Seven Years. Not One Apology for the Work.']);
            });
    }

    public function down(): void
    {
        Story::where('is_demo', true)
            ->where('status', 'interview_complete')
            ->each(fn (Story $story) => $story->episodes()->delete());
    }
};
