<?php

namespace Database\Seeders;

use App\Models\BusinessProfile;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoryInterviewSeeder extends Seeder
{
    public function run(): void
    {
        $email = $this->command->ask(
            'Seed interview for which user email?',
            User::first()?->email ?? 'support@storycreator.bot'
        );

        $user = User::where('email', $email)->firstOrFail();

        // Build the full chatLog exactly as the frontend would produce it
        $messages = [
            ['role' => 'user',      'content' => 'Please begin the interview.'],
            ['role' => 'assistant', 'content' => "I'm StoryBot. My job is to ask the right questions and turn your answers into content worth sharing. Over the next few minutes, I'll walk you through 15 questions about Torres Landscaping — where you started, what shaped you, the work you do, and where you're headed. Ready?"],
            ['role' => 'user',      'content' => '[Ready to begin]'],
            ['role' => 'assistant', 'content' => 'Describe what you were doing prior to starting your business.'],
            ['role' => 'user',      'content' => 'I spent eleven years working for a large landscaping company in Dallas. Started as a crew member pulling weeds and hauling mulch, worked my way up to crew lead, then site supervisor. By the end I was managing six crews across three commercial accounts. I knew the work inside out. I also knew exactly how the company was cutting corners on every job.'],
            ['role' => 'assistant', 'content' => "Eleven years is real time in the field. You did not just learn the work — you saw the full picture from the ground up and understood where the gaps were. That kind of experience is exactly what gives you credibility to do it differently.\n\nShare the moment you decided to start your own business."],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'Share the moment you decided to start your own business.'],
            ['role' => 'user',      'content' => 'We were finishing a big install for a corporate office park. Beautiful design, good budget. But the owner told us to swap out the specified irrigation system for a cheaper one without telling the client. I refused. He said do it or find somewhere else to work. I drove home that night and called my wife and said I think I just quit. She said good. That was a Thursday. By Monday I had a business name and a trailer.'],
            ['role' => 'assistant', 'content' => "That Thursday to Monday window says everything. You did not agonize — you moved. And the fact that your wife said \"good\" without hesitation tells me this was not a surprise to her.\n\nWho taught you the basics of the work?"],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'Who taught you the basics of the work?'],
            ['role' => 'user',      'content' => 'My uncle Ray. He had a small landscaping operation in San Antonio, nothing fancy, just residential maintenance. I used to spend summers with him when I was twelve, thirteen. He was obsessive about doing things right. He would re-edge a lawn three times if it wasn\'t clean. He told me once that the yard is the first thing people see when they come home. You\'re responsible for how someone feels when they pull into their driveway. I never forgot that.'],
            ['role' => 'assistant', 'content' => "Ray gave you something most people in this industry never get — a standard. Not just how to do the work, but why it matters. That line about the driveway is going to show up in your content.\n\nWhat important lessons did you learn the hard way?"],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What important lessons did you learn the hard way?'],
            ['role' => 'user',      'content' => 'I took on too much in year two. Said yes to everything because I was scared of slow months. Had four jobs overlapping, not enough crew, quality started slipping. Lost two clients in the same week. Both called me personally. One of them said she trusted me and she felt let down. That hurt more than losing the money. I turned down work for the first time the following month. It was the hardest call I made and the best decision I ever made.'],
            ['role' => 'assistant', 'content' => "Losing those clients back to back would break most people. The fact that you heard \"she felt let down\" and let that be the lesson — not the revenue, not the logistics — that tells me a lot about how you run things now.\n\nHow did working for someone else shape your approach or perspective?"],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'How did working for someone else shape your approach or perspective?'],
            ['role' => 'user',      'content' => 'It showed me what I never wanted to be. The company I worked for treated clients like accounts, not people. Nobody knew the client\'s name. Nobody knew what mattered to them. We showed up, did the job, moved on. I swore my business would never feel like that. Every client I have, I know their dog\'s name. I know which neighbor they don\'t get along with. I know if they\'re going through something hard. That\'s not strategy. That\'s just how I think people deserve to be treated.'],
            ['role' => 'assistant', 'content' => "Knowing the dog\'s name. That is not a detail — that is a philosophy.\n\nWhat's different about the way you work with customers or clients?"],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => "What's different about the way you work with customers or clients?"],
            ['role' => 'user',      'content' => 'I call every client personally after every major job. Not a text. A call. I ask them to walk the property and tell me honestly what they think. If something isn\'t right I come back and fix it before the invoice goes out. A lot of contractors wait for complaints. I go looking for them. I\'d rather hear it from my client than see it in a Google review.'],
            ['role' => 'assistant', 'content' => "You inverted the whole dynamic. Most contractors are hoping no one says anything. You are actively looking for the problem before it becomes one.\n\nShare an example of how you helped a customer or customers in a meaningful way."],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'Share an example of how you helped a customer or customers in a meaningful way.'],
            ['role' => 'user',      'content' => 'I had a client, a widow in her seventies, whose late husband had maintained their property himself for forty years. Big lot, established trees, rose garden he\'d planted decades ago. She called me because she couldn\'t keep up with it anymore and was thinking about selling the house. She was embarrassed about the state of it. We spent two days getting the property back. When I called her after, she was crying. She said she felt like her husband was still there. She\'s been a client for four years now. I don\'t think she\'s selling that house.'],
            ['role' => 'assistant', 'content' => "That is not landscaping. That is someone getting to stay home.\n\nWhat are some things you will not compromise on?"],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What are some things you will not compromise on?'],
            ['role' => 'user',      'content' => 'Honesty about price. I will not lowball a quote to win a job and then find reasons to add charges. I give people the real number upfront. I also won\'t let a job leave until it\'s right. I\'ve had crew members tell me a site is done and I\'ve walked it and sent everyone back. The client isn\'t there to see that. But I am.'],
            ['role' => 'assistant', 'content' => "The client is not there — but you are. That accountability to an invisible standard is rare.\n\nWhat about your business keeps you up at night?"],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What about your business keeps you up at night?'],
            ['role' => 'user',      'content' => 'Making sure my guys are okay. I have eight full-time employees. Some of them have families depending on a paycheck. When a big account goes quiet or a contract doesn\'t renew, my first thought isn\'t about me. It\'s about them. I take that seriously in a way I didn\'t expect when I started. Being a boss is heavier than it looks from the outside.'],
            ['role' => 'assistant', 'content' => "Eight families. That is a weight most people do not see when they look at a small business.\n\nWhat about your business motivates you in the morning?"],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What about your business motivates you in the morning?'],
            ['role' => 'user',      'content' => 'Honestly, the crew. When I pull up to a job site and everyone\'s already set up and working without being told, I feel something I can\'t fully explain. These are people who chose to be here. That matters to me. I also still love the work itself. There\'s something satisfying about a finished property. Before and after is real. You can see what you did.'],
            ['role' => 'assistant', 'content' => "People who chose to be there. That is the difference between a team and a roster.\n\nWho has supported or influenced you throughout this journey?"],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'Who has supported or influenced you throughout this journey?'],
            ['role' => 'user',      'content' => 'My wife Sandra has been there for every hard decision. She kept her job the first two years so we had something to fall back on. She never complained. She believed in this before I fully did. And my uncle Ray, who passed in 2019, but whose voice I still hear when I\'m tempted to rush something. He would not be impressed by shortcuts.'],
            ['role' => 'assistant', 'content' => "Sandra and Ray — one kept the lights on, one kept the standards up. You carry both.\n\nAny community or charitable activities you'd like to highlight?"],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => "Any community or charitable activities you'd like to highlight?"],
            ['role' => 'user',      'content' => 'We do pro bono maintenance for two group homes in our area that house adults with disabilities. Started three years ago, one of my crew members suggested it. We go out once a month. The residents sometimes come outside and watch. A few of them help with small things. It\'s become one of my favorite days of the month.'],
            ['role' => 'assistant', 'content' => "A crew member suggested it and you said yes. That tells me what kind of culture you\'ve built.\n\nWhat stage is your business in: starting, growing, or maintaining?"],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What stage is your business in: starting, growing, or maintaining?'],
            ['role' => 'user',      'content' => 'Growing, carefully. I could take on more but I won\'t until I\'m sure the quality stays the same. I\'ve seen what happens when landscaping companies scale too fast. I\'m not in a rush.'],
            ['role' => 'assistant', 'content' => "Growing carefully is still a choice. Most people do not have the discipline to say no to revenue.\n\nWhat are you most proud of?"],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What are you most proud of?'],
            ['role' => 'user',      'content' => "That I've never had to apologize for a job. In seven years, I have never had to say we did bad work. I've had to say we were slower than expected, or that something took longer than the quote. But the work itself, I stand behind every inch of it."],
            ['role' => 'assistant', 'content' => "Seven years. Not one apology for the work. That is a record worth protecting.\n\nWhat would you hope your customers say about you?"],
            ['role' => 'user',      'content' => '[Ready for next question]'],
            ['role' => 'assistant', 'content' => 'What would you hope your customers say about you?'],
            ['role' => 'user',      'content' => "That they trust us completely. That they don't have to check up on us or wonder if we showed up. That having us there is one less thing they have to worry about. That's it. I want to be the contractor people don't have to think about, because they already know it's handled."],
            ['role' => 'assistant', 'content' => 'That is everything I need. You have given me everything I need to tell your story the right way. Give me a moment while I put your story library together.'],
        ];

        $profile = BusinessProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => 'Torres Landscaping',
                'business_url' => 'torreslandscaping.com',
                'industry' => 'Landscaping',
                'answers' => $messages,
            ]
        );

        // Remove any existing in-progress story for this user so we can create a fresh one
        Story::where('user_id', $user->id)
            ->whereIn('status', ['interviewing', 'interview_complete'])
            ->delete();

        $story = Story::create([
            'user_id' => $user->id,
            'business_profile_id' => $profile->id,
            'title' => null,
            'status' => 'interview_complete',
        ]);

        $this->command->info("✓ Story #{$story->id} created for {$user->email}");
        $this->command->info("  → Go to: /stories/{$story->id}/resume to generate episodes");
    }
}
