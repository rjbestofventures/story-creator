<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnswersPlaybackTest extends TestCase
{
    use RefreshDatabase;

    private function story(User $user): Story
    {
        $profile = BusinessProfile::create([
            'user_id' => $user->id,
            'business_name' => 'South Florida Mattress Direct',
            'answers' => [
                ['role' => 'assistant', 'content' => 'Describe what you were doing prior to starting your business.'],
                ['role' => 'user', 'content' => 'I started in the furnishings business up in the Northeast.'],
            ],
        ]);

        return Story::create([
            'user_id' => $user->id,
            'business_profile_id' => $profile->id,
            'title' => 'Stories From the Floor',
            'status' => 'draft',
        ]);
    }

    private function fakeTts(): void
    {
        Http::fake(['api.openai.com/*' => Http::response('mp3-bytes', 200)]);
    }

    public function test_question_is_read_in_the_interview_bot_voice(): void
    {
        $this->fakeTts();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('stories.answers.speak', $this->story($user)), [
                'number' => 1, 'part' => 'question', 'voice' => 'male',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg');

        Http::assertSent(fn ($request) => $request['voice'] === 'nova'
            && $request['input'] === 'Describe what you were doing prior to starting your business.');
    }

    public function test_answer_follows_the_male_female_toggle(): void
    {
        $user = User::factory()->create();
        $story = $this->story($user);

        $this->fakeTts();
        $this->actingAs($user)->post(route('stories.answers.speak', $story), [
            'number' => 1, 'part' => 'answer', 'voice' => 'male',
        ])->assertOk();
        Http::assertSent(fn ($request) => $request['voice'] === 'onyx');

        $this->fakeTts();
        $this->actingAs($user)->post(route('stories.answers.speak', $story), [
            'number' => 1, 'part' => 'answer', 'voice' => 'female',
        ])->assertOk();
        Http::assertSent(fn ($request) => $request['voice'] === 'nova');
    }

    public function test_voice_must_be_male_or_female(): void
    {
        $this->fakeTts();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('stories.answers.speak', $this->story($user)), [
                'number' => 1, 'part' => 'answer', 'voice' => 'robot',
            ])
            ->assertSessionHasErrors('voice');
    }

    public function test_another_user_cannot_play_the_interview(): void
    {
        $this->fakeTts();
        $owner = User::factory()->create();
        $story = $this->story($owner);

        $this->actingAs(User::factory()->create())
            ->post(route('stories.answers.speak', $story), [
                'number' => 1, 'part' => 'answer', 'voice' => 'male',
            ])
            ->assertForbidden();
    }

    public function test_admin_granted_credits_are_labelled_bonus_pack(): void
    {
        $user = User::factory()->create();
        $user->purchases()->create([
            'credit_pack_id' => null,
            'credits_granted' => 10,
            'amount_paid' => 0,
            'source' => 'gift',
            'purchased_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('billing.history'))
            ->assertInertia(fn ($page) => $page
                ->component('Billing/History')
                ->where('purchases.0.pack_label', 'Bonus Pack'));
    }
}
