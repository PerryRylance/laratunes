<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestsAreRedirectedToTheLoginPage(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function testAuthenticatedUsersCanVisitTheDashboard(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')->assertStatus(200);
    }
}
