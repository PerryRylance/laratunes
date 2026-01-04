<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\ConfigureStreamCallout;
use App\Filament\Widgets\DiscoverMediaCallout;
use App\Filament\Widgets\FileMissingCallout;
use App\Filament\Widgets\StatusWidget;
use App\Jobs\CreateTrackJob;
use App\Models\Setting;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
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

	#[TestWith([Setting::STREAM_URL, 'rtmp://test.stream'], 'url without key')]
	#[TestWith([Setting::STREAM_KEY, 'a-secret-key'], 'key without url')]
	public function testSeeConfigureStreamCalloutWhenSettingsMissing(string $key, string $value): void
	{
		Setting::factory()
			->defaults()
			->create();

		Setting::factory()
			->create([
				'name' => $key,
				'value' => $value,
			]);

		Livewire::test(Dashboard::class)
			->assertSeeLivewire(ConfigureStreamCallout::class);
	}

	public function testDontSeeConfigureStreamCalloutWhenSettingsPresent(): void
	{
		Setting::factory()
			->complete()
			->create();

		Livewire::test(Dashboard::class)
			->assertDontSeeLivewire(ConfigureStreamCallout::class);
	}

	public function testDontSeeReasonsInStatusWidgetWhenSettingsPresent(): void
	{
		Setting::factory()
			->complete()
			->create();

		Track::factory()
			->uploaded()
			->create();

		Livewire::test(StatusWidget::class)
			->assertDontSee('The broadcast cannot be started:')
			->assertDontSee('The stream is not configured.')
			->assertDontSee('There are no tracks in your library.');
	}

	public function testSeeReasonInStatusWidgetWhenNoTracksPresent(): void
	{
		Setting::factory()
			->complete()
			->create();

		Livewire::test(StatusWidget::class)
			->assertDontSee('The stream is not configured.')
			->assertSee('There are no tracks in your library.');
	}

	#[TestWith([Setting::STREAM_URL, 'rtmp://test.stream'], 'url without key')]
	#[TestWith([Setting::STREAM_KEY, 'a-secret-key'], 'key without url')]
	public function testSeeReasonInStatusWidgetWhenSettingsMissing(): void
	{
		Track::factory()->uploaded()->create();

		Livewire::test(StatusWidget::class)
			->assertDontSee('There are no tracks in your library.')
			->assertSee('The stream is not configured.');
	}
}
