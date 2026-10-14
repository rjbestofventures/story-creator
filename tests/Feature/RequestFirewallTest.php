<?php

namespace Tests\Feature;

use App\Http\Middleware\BlockSuspiciousRequests;
use App\Models\RequestLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RequestFirewallTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanner_probes_get_a_bare_404_and_are_logged_as_blocked(): void
    {
        foreach (['/config.json', '/.env', '/~apache', '/debug/default/view?panel=config'] as $path) {
            $this->get($path)->assertNotFound();
        }

        $this->assertSame(4, RequestLog::where('blocked', true)->count());
        $this->assertDatabaseHas('request_logs', ['path' => '/config.json', 'status' => 404, 'blocked' => true]);
    }

    public function test_probe_pattern_matches_scanner_paths_but_not_app_routes(): void
    {
        $firewall = new BlockSuspiciousRequests;

        foreach (['server/config.json', 'config/secrets.yml', 'app/config/parameters.yml', '_wpeprivate/config.json', 'sapi/debug/default/view', 'env.backup', 'config.js.bak', 'config.inc.bak', 'setup.sql', 'global.asax.bak', 'global.asa.tmp', 'global.asa.old', 'wp-login.php', '.git/config'] as $path) {
            $this->assertTrue($firewall->isProbe(Request::create("/{$path}")), "{$path} should be blocked");
        }

        foreach (['', 'terms', 'signup', 'stories/12/episodes/3/speak', 'admin/logs', 'build/assets/app.js', 'images/storycreator-logo.png', 'stripe/webhook'] as $path) {
            $this->assertFalse($firewall->isProbe(Request::create("/{$path}")), "{$path} should pass");
        }
    }

    public function test_repeated_probes_ban_the_ip(): void
    {
        for ($i = 0; $i < BlockSuspiciousRequests::STRIKES_BEFORE_BAN; $i++) {
            $this->get("/backup{$i}.sql");
        }

        $this->get('/terms')->assertForbidden();
    }

    public function test_normal_pages_pass_and_are_logged(): void
    {
        $this->get('/terms')->assertOk();

        $this->assertDatabaseHas('request_logs', ['method' => 'GET', 'path' => '/terms', 'status' => 200, 'blocked' => false]);
    }

    public function test_credentials_in_urls_are_redacted_from_the_log(): void
    {
        $this->get('/reset-password/abc123secret?email=a@b.com');
        $this->get('/login/link/7/stories/deadbeef?expires=1&signature=xyz');
        $this->get('/terms?utm_source=mail');

        $this->assertDatabaseHas('request_logs', ['path' => '/reset-password/REDACTED?email=REDACTED']);
        $this->assertDatabaseHas('request_logs', ['path' => '/login/link/7/stories/REDACTED?expires=1&signature=REDACTED']);
        $this->assertDatabaseHas('request_logs', ['path' => '/terms?utm_source=mail']);
        $this->assertDatabaseMissing('request_logs', ['path' => '/reset-password/abc123secret?email=a@b.com']);
    }

    public function test_super_admin_sees_the_logs_page(): void
    {
        Role::findOrCreate('super_admin');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->get('/config.json');

        $this->actingAs($admin)->get(route('admin.logs.index', ['status' => 'blocked']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Logs')
                ->where('requests.data.0.path', '/config.json')
                ->where('summary.blocked', 1));

        $this->actingAs($admin)->get(route('admin.logs.index', ['tab' => 'errors']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('tab', 'errors'));
    }

    public function test_regular_users_cannot_open_the_logs_page(): void
    {
        Role::findOrCreate('user');
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)->get(route('admin.logs.index'))->assertForbidden();
    }
}
