<?php

namespace Database\Seeders;

use App\Models\BusinessProfile;
use App\Models\Episode;
use App\Models\Plan;
use App\Models\Story;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure plans exist
        $plan = Plan::firstOrCreate(
            ['slug' => 'professional'],
            [
                'label' => 'Professional',
                'episode_limit' => 50,
                'stories_per_month' => 10,
                'refine_monthly' => 20,
                'price_monthly' => 25,
                'price_yearly' => 250,
                'trial_months' => 0,
                'is_active' => true,
            ]
        );

        // Demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@storycreator.bot'],
            [
                'name' => 'Alex Rivera',
                'password' => Hash::make('password'),
            ]
        );
        $user->syncRoles(['user']);

        UserSubscription::firstOrCreate(
            ['user_id' => $user->id],
            [
                'plan_id' => $plan->id,
                'status' => 'active',
                'story_credits' => 8,
                'refine_credits' => 15,
                'episode_limit' => 50,
                'starts_at' => now(),
            ]
        );

        // Business profile
        $profile = BusinessProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => 'Rivera Creative Co.',
                'business_url' => 'https://riveracreative.co',
                'industry' => 'Brand Strategy & Content',
                'answers' => [
                    ['section' => 'Origin',          'question' => 'How did you end up doing what you do today?',                                                               'answer' => 'I left a corporate marketing job after 8 years because I kept watching small businesses get outcompeted — not because of product quality, but because nobody knew their story. I started Rivera Creative Co. from my kitchen table in 2019 with one laptop and a belief that every founder has a story worth telling.'],
                    ['section' => 'Origin',          'question' => 'Looking back, which experiences really prepared you to start your own business?',                           'answer' => 'A decade running campaigns for big brands taught me the mechanics of marketing, but my real education came from the side projects — helping friends launch their businesses for free, staying up late writing copy that actually felt human. That contrast between what big brands do and what small businesses need became the foundation of everything.'],
                    ['section' => 'Origin',          'question' => 'What was the moment you knew you were ready to do this on your own?',                                        'answer' => 'A client told me that my campaign was the first time she felt like someone actually understood her business — not just her product, but why she started it. That hit me differently. I realized I was building something for someone else that I should be building for myself.'],
                    ['section' => 'Origin',          'question' => 'What is the most important thing you learned the hard way?',                                                 'answer' => 'That you can\'t serve everyone. My first year I took every client who said yes. I was exhausted and the work was mediocre. The best decision I ever made was getting specific about who I work with and why.'],
                    ['section' => 'Origin',          'question' => 'How do your past experiences show up in the way you run your business now?',                                 'answer' => 'I bring a lot of structure to creative work — which is unusual. Most creatives resist process. But I saw too many good ideas die in big organizations because no one had a clear system for execution. I built that into the way I work from day one.'],
                    ['section' => 'The Work',        'question' => 'What would you like your team and customers to say about working with you?',                                  'answer' => 'That I listened before I spoke. That I made them feel like their business mattered — because it does.'],
                    ['section' => 'The Work',        'question' => 'Do you remember your worst customer service nightmare, and how you resolved it?',                            'answer' => 'I once completely misread what a client wanted — delivered weeks of work that missed the mark. Instead of defending the work, I scrapped everything and started over. We went back to the beginning together. That project ended up being one of my best case studies.'],
                    ['section' => 'The Work',        'question' => 'What is one thing you absolutely refuse to compromise on in your business?',                                  'answer' => 'Authenticity in the stories I help create. I\'ve turned down clients who wanted to exaggerate results or fabricate testimonials. Your real story is always more powerful than a made-up one.'],
                    ['section' => 'The Work',        'question' => 'Is there any part of running the business that still keeps you up at night?',                                'answer' => 'Finding the balance between doing the work I love and running the business I\'ve built. The admin, the sales, the contracts — none of that is why I started. I\'m still figuring out how to protect space for the creative work.'],
                    ['section' => 'The Work',        'question' => 'What keeps you motivated to do this work day after day?',                                                    'answer' => 'The moment a client reads the first draft and goes quiet. Not because they\'re disappointed — because they\'re reading something that sounds exactly like them, better than they could have said it themselves.'],
                    ['section' => 'The Big Picture', 'question' => 'Has there been anyone who has always had your back along the way?',                                          'answer' => 'My partner. She believed in this before I did. On the days I almost quit, she reminded me why I started.'],
                    ['section' => 'The Big Picture', 'question' => 'Are there any causes, communities, or local efforts that matter to you or your business?',                   'answer' => 'I mentor two small business owners in my community pro bono every year — always people who remind me of where I started. No budget, big vision, no idea how to tell their story.'],
                    ['section' => 'The Big Picture', 'question' => 'How would you describe where your business is right now?',                                                   'answer' => 'We\'re at an inflection point. The work is stronger than ever, the clients are better fits, and for the first time I feel like the business can grow without me being stretched thin. We\'re building infrastructure while trying not to lose what makes us good.'],
                    ['section' => 'The Big Picture', 'question' => 'What is something you are proud of but do not usually talk about?',                                          'answer' => 'Three of my clients have gone on to hire their first full-time employees after working with us. The story work helped them attract the right kind of attention to make that leap. I never talk about that enough.'],
                    ['section' => 'The Big Picture', 'question' => 'If people were talking about your business when you were not in the room, what would you hope they would say?', 'answer' => 'That we made them feel seen. That the work felt honest. That it actually sounded like them.'],
                    ['section' => 'Anything Else',   'question' => 'Is there anything important about you or your business that we have not talked about yet?',                   'answer' => 'Just that this work matters to me in a way I didn\'t expect. I started Rivera Creative thinking it was a career move. It turned into a calling.'],
                ],
            ]
        );

        // Sample story with episodes
        $story = Story::firstOrCreate(
            ['user_id' => $user->id, 'title' => 'How a Kitchen Table Became a Brand Studio'],
            [
                'business_profile_id' => $profile->id,
                'status' => 'draft',
            ]
        );

        if ($story->episodes()->count() === 0) {
            $episodes = [
                [
                    'episode_number' => 1,
                    'title' => 'The Day I Walked Away',
                    'content' => "Eight years. That's how long I told myself the corporate job was just temporary.\n\nI had the title, the salary, the benefits. But every week I sat in a meeting watching another small business owner's campaign get outspent by a competitor with a fraction of the soul and ten times the budget.\n\nThe breaking point wasn't dramatic. It was a Tuesday. A family-owned furniture maker in Ohio had just lost a major account to a big-box chain. Their product was better. Their story was incredible — three generations, a craft passed down through a family that survived everything the 20th century threw at them.\n\nBut nobody knew. Because nobody had told it.\n\nI drove home, sat at my kitchen table, and opened a blank document. I didn't know what I was starting. I just knew I couldn't keep sitting in those meetings.\n\nWhat's the story your business is sitting on that the world hasn't heard yet?",
                    'format' => 'social',
                ],
                [
                    'episode_number' => 2,
                    'title' => 'The Kitchen Table Years',
                    'content' => "My first 'office' was a kitchen table in a 600 square foot apartment.\n\nOne laptop. A secondhand ring light I found on Facebook Marketplace. A notebook I'd been carrying around for three years, full of half-formed ideas about storytelling and why it matters.\n\nI landed my first client through a LinkedIn post I almost didn't publish. It was a yoga studio owner who said my words made her feel 'seen for the first time in business.' That sentence hit me harder than any revenue milestone ever has.\n\nThose first six months were terrifying and clarifying in equal measure. I ate a lot of instant ramen. I had more doubt than confidence most mornings. But every single client conversation reminded me why I left.\n\nThere's something that happens when a business owner finally articulates their real story out loud. They sit up straighter. Their voice changes. They remember why they started.\n\nThat's the thing I get to witness every week now.\n\nWhen did you last tell someone the real reason you started your business?",
                    'format' => 'social',
                ],
                [
                    'episode_number' => 3,
                    'title' => 'What I Actually Do',
                    'content' => "People ask me what I do and I used to say 'content strategy.' It's accurate, technically. But it misses the point.\n\nWhat I actually do is listen. Really listen. To the moment someone decided to start. To the client who changed everything. To the failure they almost didn't recover from and what it taught them.\n\nThen I help turn that into words — posts, stories, newsletters, videos — that feel like *them*. Not a polished brand voice. Not corporate-speak dressed up in casual clothes. Them.\n\nMy clients are mostly small teams. A physiotherapist running her own clinic. A custom furniture maker who's been building things by hand for 20 years. A tech founder who's tired of sounding like every other tech founder.\n\nThey're all brilliant at their craft. None of them came to business school to learn how to talk about themselves on the internet.\n\nThat's where I come in.\n\nWhat's the part of your story that you've always found hardest to put into words?",
                    'format' => 'social',
                ],
                [
                    'episode_number' => 4,
                    'title' => 'The Bigger Game',
                    'content' => "I've been thinking a lot lately about what I'm actually building toward.\n\nNot just my business — the whole thing. The ecosystem of small businesses that make up the actual texture of every city and town. The people who take the risk, build the thing, show up every day because they believe in what they're doing.\n\nThey lose to bigger competitors constantly — not on quality, not on care, not on expertise. On visibility. On the ability to be seen, heard, remembered.\n\nI think that's changing. Slowly, but it's changing.\n\nWhen someone shares a real story — not a polished ad, not a highlight reel — something different happens in the reader. A kind of recognition. *That's a real person. That's a real business. I trust that.*\n\nThe next generation of trusted brands won't be built by the ones with the biggest ad budgets. They'll be built by the ones with the most honest stories.\n\nThat's the world I'm working toward. One story at a time.\n\nWhat would change for your business if more people knew the real story behind it?",
                    'format' => 'social',
                ],
                [
                    'episode_number' => 5,
                    'title' => 'What I\'ve Learned About Trust',
                    'content' => "Five years in, here's what I know about building trust through content:\n\nIt's not about being perfect. It's not about having a flawless origin story or a dramatic pivot. The businesses that build the deepest audience trust are the ones that share the real version — the uncertain version, the still-figuring-it-out version.\n\nPeople can smell polish from a mile away. They can also smell real.\n\nThe best piece of content I've ever helped create was a post a client wrote about a project that went completely wrong. How she handled it. What she refunded. What she learned. That post got more engagement than anything she'd posted in two years. More DMs. More new clients.\n\nNot because it made her look perfect. Because it made her look human.\n\nThat's the counterintuitive thing about authentic storytelling in business: your most relatable moments are often your most valuable ones.\n\nWhat's a story from your business that you've been too afraid to tell?",
                    'format' => 'social',
                ],
            ];

            foreach ($episodes as $ep) {
                Episode::create(array_merge($ep, ['story_id' => $story->id]));
            }
        }
    }
}
