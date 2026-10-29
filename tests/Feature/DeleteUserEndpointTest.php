<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DeleteUserEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.provision_api_token' => 'test-token']);
    }

    private function destroy(array $payload): TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer test-token')
            ->deleteJson('/api/provision/user', $payload);
    }

    public function test_it_deletes_the_member_with_that_email(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->destroy(['email' => $user->email])
            ->assertOk()
            ->assertJson(['deleted' => true, 'email' => $user->email]);

        $this->assertModelMissing($user);
        $this->assertModelExists($other);
    }

    public function test_an_unknown_email_is_a_404(): void
    {
        $this->destroy(['email' => 'nobody@example.com'])->assertNotFound();
    }

    public function test_it_requires_an_email(): void
    {
        $this->destroy([])->assertUnprocessable();
    }

    public function test_it_requires_a_valid_token(): void
    {
        $user = User::factory()->create();

        $this->deleteJson('/api/provision/user', ['email' => $user->email])->assertUnauthorized();

        $this->assertModelExists($user);
    }
}
