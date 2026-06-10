<?php

namespace App\Actions;

use App\Models\BusinessProfile;
use App\Models\Episode;
use App\Models\Story;
use App\Models\User;

class SeedDemoStoriesForUser
{
    public function handle(User $user): Story
    {
        $this->seedRiveraCreative($user);

        return $this->seedTorresLandscaping($user);
    }

    private function seedRiveraCreative(User $user): void
    {
        $profile = BusinessProfile::create([
            'user_id' => $user->id,
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
        ]);

        $story = Story::create([
            'user_id' => $user->id,
            'business_profile_id' => $profile->id,
            'title' => 'How a Kitchen Table Became a Brand Studio',
            'status' => 'draft',
            'is_demo' => true,
        ]);

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

    private function seedTorresLandscaping(User $user): Story
    {
        $messages = [
            ['role' => 'user',      'content' => 'Please begin the interview.'],
            ['role' => 'assistant', 'content' => "I'm StoryBot. My job is to ask the right questions and turn your answers into content worth sharing. Over the next few minutes, I'll walk you through 15 questions about Torres Landscaping — where you started, what shaped you, the work you do, and where you're headed. Ready?"],
            ['role' => 'user',      'content' => '[Ready to begin]'],
            ['role' => 'assistant', 'content' => 'Describe what you were doing prior to starting your business.'],
            ['role' => 'user',      'content' => 'I spent eleven years working for a large landscaping company in Dallas. Started as a crew member pulling weeds and hauling mulch, worked my way up to crew lead, then site supervisor. By the end I was managing six crews across three commercial accounts. I knew the work inside out. I also knew exactly how the company was cutting corners on every job.'],
            ['role' => 'assistant', 'content' => 'Eleven years is real time in the field. You did not just learn the work — you saw the full picture from the ground up and understood where the gaps were. That kind of experience is exactly what gives you credibility to do it differently.'],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'Share the moment you decided to start your own business.'],
            ['role' => 'user',      'content' => 'We were finishing a big install for a corporate office park. Beautiful design, good budget. But the owner told us to swap out the specified irrigation system for a cheaper one without telling the client. I refused. He said do it or find somewhere else to work. I drove home that night and called my wife and said I think I just quit. She said good. That was a Thursday. By Monday I had a business name and a trailer.'],
            ['role' => 'assistant', 'content' => 'That Thursday to Monday window says everything. You did not agonize — you moved. And the fact that your wife said "good" without hesitation tells me this was not a surprise to her.'],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'Who taught you the basics of the work?'],
            ['role' => 'user',      'content' => 'My uncle Ray. He had a small landscaping operation in San Antonio, nothing fancy, just residential maintenance. I used to spend summers with him when I was twelve, thirteen. He was obsessive about doing things right. He would re-edge a lawn three times if it wasn\'t clean. He told me once that the yard is the first thing people see when they come home. You\'re responsible for how someone feels when they pull into their driveway. I never forgot that.'],
            ['role' => 'assistant', 'content' => 'Ray gave you something most people in this industry never get — a standard. Not just how to do the work, but why it matters. That line about the driveway is going to show up in your content.'],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What important lessons did you learn the hard way?'],
            ['role' => 'user',      'content' => 'I took on too much in year two. Said yes to everything because I was scared of slow months. Had four jobs overlapping, not enough crew, quality started slipping. Lost two clients in the same week. Both called me personally. One of them said she trusted me and she felt let down. That hurt more than losing the money. I turned down work for the first time the following month. It was the hardest call I made and the best decision I ever made.'],
            ['role' => 'assistant', 'content' => 'Losing those clients back to back would break most people. The fact that you heard "she felt let down" and let that be the lesson — not the revenue, not the logistics — that tells me a lot about how you run things now.'],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'How did working for someone else shape your approach or perspective?'],
            ['role' => 'user',      'content' => 'It showed me what I never wanted to be. The company I worked for treated clients like accounts, not people. Nobody knew the client\'s name. Nobody knew what mattered to them. We showed up, did the job, moved on. I swore my business would never feel like that. Every client I have, I know their dog\'s name. I know which neighbor they don\'t get along with. I know if they\'re going through something hard. That\'s not strategy. That\'s just how I think people deserve to be treated.'],
            ['role' => 'assistant', 'content' => "Knowing the dog\'s name. That is not a detail — that is a philosophy."],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => "What's different about the way you work with customers or clients?"],
            ['role' => 'user',      'content' => 'I call every client personally after every major job. Not a text. A call. I ask them to walk the property and tell me honestly what they think. If something isn\'t right I come back and fix it before the invoice goes out. A lot of contractors wait for complaints. I go looking for them. I\'d rather hear it from my client than see it in a Google review.'],
            ['role' => 'assistant', 'content' => 'You inverted the whole dynamic. Most contractors are hoping no one says anything. You are actively looking for the problem before it becomes one.'],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'Share an example of how you helped a customer or customers in a meaningful way.'],
            ['role' => 'user',      'content' => 'I had a client, a widow in her seventies, whose late husband had maintained their property himself for forty years. Big lot, established trees, rose garden he\'d planted decades ago. She called me because she couldn\'t keep up with it anymore and was thinking about selling the house. She was embarrassed about the state of it. We spent two days getting the property back. When I called her after, she was crying. She said she felt like her husband was still there. She\'s been a client for four years now. I don\'t think she\'s selling that house.'],
            ['role' => 'assistant', 'content' => 'That is not landscaping. That is someone getting to stay home.'],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What are some things you will not compromise on?'],
            ['role' => 'user',      'content' => 'Honesty about price. I will not lowball a quote to win a job and then find reasons to add charges. I give people the real number upfront. I also won\'t let a job leave until it\'s right. I\'ve had crew members tell me a site is done and I\'ve walked it and sent everyone back. The client isn\'t there to see that. But I am.'],
            ['role' => 'assistant', 'content' => 'The client is not there — but you are. That accountability to an invisible standard is rare.'],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What about your business keeps you up at night?'],
            ['role' => 'user',      'content' => 'Making sure my guys are okay. I have eight full-time employees. Some of them have families depending on a paycheck. When a big account goes quiet or a contract doesn\'t renew, my first thought isn\'t about me. It\'s about them. I take that seriously in a way I didn\'t expect when I started. Being a boss is heavier than it looks from the outside.'],
            ['role' => 'assistant', 'content' => 'Eight families. That is a weight most people do not see when they look at a small business.'],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What about your business motivates you in the morning?'],
            ['role' => 'user',      'content' => 'Honestly, the crew. When I pull up to a job site and everyone\'s already set up and working without being told, I feel something I can\'t fully explain. These are people who chose to be here. That matters to me. I also still love the work itself. There\'s something satisfying about a finished property. Before and after is real. You can see what you did.'],
            ['role' => 'assistant', 'content' => 'People who chose to be there. That is the difference between a team and a roster.'],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'Who has supported or influenced you throughout this journey?'],
            ['role' => 'user',      'content' => 'My wife Sandra has been there for every hard decision. She kept her job the first two years so we had something to fall back on. She never complained. She believed in this before I fully did. And my uncle Ray, who passed in 2019, but whose voice I still hear when I\'m tempted to rush something. He would not be impressed by shortcuts.'],
            ['role' => 'assistant', 'content' => 'Sandra and Ray — one kept the lights on, one kept the standards up. You carry both.'],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => "Any community or charitable activities you'd like to highlight?"],
            ['role' => 'user',      'content' => 'We do pro bono maintenance for two group homes in our area that house adults with disabilities. Started three years ago, one of my crew members suggested it. We go out once a month. The residents sometimes come outside and watch. A few of them help with small things. It\'s become one of my favorite days of the month.'],
            ['role' => 'assistant', 'content' => "A crew member suggested it and you said yes. That tells me what kind of culture you\'ve built."],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What stage is your business in: starting, growing, or maintaining?'],
            ['role' => 'user',      'content' => 'Growing, carefully. I could take on more but I won\'t until I\'m sure the quality stays the same. I\'ve seen what happens when landscaping companies scale too fast. I\'m not in a rush.'],
            ['role' => 'assistant', 'content' => 'Growing carefully is still a choice. Most people do not have the discipline to say no to revenue.'],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What are you most proud of?'],
            ['role' => 'user',      'content' => "That I've never had to apologize for a job. In seven years, I have never had to say we did bad work. I've had to say we were slower than expected, or that something took longer than the quote. But the work itself, I stand behind every inch of it."],
            ['role' => 'assistant', 'content' => 'Seven years. Not one apology for the work. That is a record worth protecting.'],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What would you hope your customers say about you?'],
            ['role' => 'user',      'content' => "That they trust us completely. That they don't have to check up on us or wonder if we showed up. That having us there is one less thing they have to worry about. That's it. I want to be the contractor people don't have to think about, because they already know it's handled."],
            ['role' => 'assistant', 'content' => 'That is everything I need. You have given me everything I need to tell your story the right way. Give me a moment while I put your story library together.'],
        ];

        $profile = BusinessProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Torres Landscaping',
            'business_url' => 'torreslandscaping.com',
            'industry' => 'Landscaping',
            'answers' => $messages,
        ]);

        $story = Story::create([
            'user_id' => $user->id,
            'business_profile_id' => $profile->id,
            'title' => 'Seven Years. Not One Apology for the Work.',
            'status' => 'interview_complete',
            'is_demo' => true,
        ]);

        $torresEpisodes = [
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

        foreach ($torresEpisodes as $ep) {
            Episode::create(array_merge($ep, ['story_id' => $story->id]));
        }

        return $story;
    }
}
