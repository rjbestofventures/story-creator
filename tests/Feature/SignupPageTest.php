<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SignupPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_page_renders_with_plan_links(): void
    {
        config(['services.signup_links' => ['silver' => 'https://pay.example/silver', 'gold' => 'https://pay.example/gold']]);

        $this->get(route('signup'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Signup')
                ->where('links.silver', 'https://pay.example/silver')
                ->where('links.gold', 'https://pay.example/gold'));
    }

    public function test_trial_form_on_signup_page_creates_a_complimentary_trial(): void
    {
        Http::fake();
        Notification::fake();
        Role::findOrCreate('user');

        $this->from(route('signup'))
            ->post(route('trial.signup.submit'), [
                'first_name' => 'Jamie',
                'last_name' => 'Rivera',
                'phone' => '3478245640',
                'email' => 'jamie@example.com',
            ])
            ->assertRedirect(route('signup'));

        $this->assertTrue(User::where('email', 'jamie@example.com')->first()->is_temporary_vbp);
    }
}
