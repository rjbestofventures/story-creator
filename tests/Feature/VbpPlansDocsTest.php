<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VbpPlansDocsTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_open_the_vbp_plans_documentation(): void
    {
        $this->get('/docs/vbp-plans')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Docs/VbpPlans')
                ->where('vbpPlans.2', ['key' => 'other', 'label' => 'Other', 'credits' => 36]));
    }
}
