<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\ConfigureStreamCallout;
use App\Filament\Widgets\DiscoverMediaCallout;
use App\Filament\Widgets\FileMissingCallout;
use App\Jobs\CreateTrackJob;
use App\Models\Track;
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

	public function testSeeDiscoverMediaCalloutWhenNoTracksPresent(): void
	{
		$this->actingAs(User::factory()->admin()->create());

		Livewire::test(Dashboard::class)
			->assertSeeLivewire(DiscoverMediaCallout::class)
			->assertSee('No tracks in library');
	}

	public function testDiscoverMediaCalloutShowsProgressWhenJobsQueued(): void
	{
		$this->actingAs(User::factory()->admin()->create());

		CreateTrackJob::dispatch('test.mp3');

		Livewire::test(Dashboard::class)
			->assertSeeLivewire(DiscoverMediaCallout::class)
			->assertSee('Discovery in progress');
	}

	public function testDontSeeDiscoverMediaCalloutWhenNoJobsQueuedAndTracksPresent(): void
	{
		$this->actingAs(User::factory()->admin()->create());

		Track::factory()->uploaded()->create();

		Livewire::test(Dashboard::class)
			->assertDontSeeLivewire(DiscoverMediaCallout::class);
	}

	public function testSeeConfigureStreamCalloutWhenSettingsMissing(): void
	{
		$this->actingAs(User::factory()->admin()->create());

		Livewire::test(Dashboard::class)
			->assertSeeLivewire(ConfigureStreamCallout::class);
	}

	public function testDontSeeConfigureStreamCalloutWhenSettingsPresent(): void {}

	public function testSeeReasonInStatusWidgetWhenNoTracksPresent(): void {}

	public function testSeeReasonInStatusWidgetWhenSettingsMissing(): void {}
}
