<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\FileMissingCallout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

	public function testFileMissingCalloutNotAutoDiscovered(): void
	{
		$this->actingAs(User::factory()->admin()->create());

		Livewire::test(Dashboard::class)
			->assertDontSeeLivewire(FileMissingCallout::class);
	}

	public function testSeeDiscoverMediaCalloutWhenNoTracksPresent(): void {}

	public function testDontSeeDiscoverMediaCalloutWhenTracksPresent(): void {}

	public function testSeeConfigureStreamCalloutWhenSettingsMissing(): void {}

	public function testDontSeeConfigureStreamCalloutWhenSettingsPresent(): void {}

	public function testDontSeeStatusWidgetWhenSettingsMissing(): void {}
}
