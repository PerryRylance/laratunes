<?php

namespace Tests\Feature;

use App\Exceptions\YtDlpException;
use App\Facades\YtDlp;
use App\Filament\Resources\Tracks\Pages\CreateTrackFromUrl;
use App\Filament\Resources\Tracks\Pages\ListTracks;
use App\Models\Track;
use Filament\Notifications\Livewire\Notifications;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\AdminTestCase;

class CreateTrackFromUrlTest extends AdminTestCase
{
	private function fakeSuccessfulDownload(string $fixture = 'test.mp3'): void
	{
		YtDlp::expects('download')
			->once()
			->andReturnUsing(function (string $url, string $destination) use ($fixture) {

				$path = str_replace('%(ext)s', 'mp3', $destination);

				file_put_contents($path, file_get_contents("./tests/Fixtures/media/$fixture"));

			});
	}

	public function testButtonHiddenFromIndexWhenYtDlpNotInstalled(): void
	{
		YtDlp::shouldReceive('installed')->andReturn(false);

		Livewire::test(ListTracks::class)
			->assertDontSee('Create from URL');
	}

	public function testButtonVisibleOnIndexWhenYtDlpInstalled(): void
	{
		YtDlp::shouldReceive('installed')->andReturn(true);

		Livewire::test(ListTracks::class)
			->assertSee('Create from URL');
	}

	public function testLoadCreateFromUrlPage(): void
	{
		Livewire::test(CreateTrackFromUrl::class)
			->assertOk();
	}

	public function testCreatesTrackFromSuccessfulDownload(): void
	{
		$this->fakeSuccessfulDownload();

		Livewire::test(CreateTrackFromUrl::class)
			->fillForm([
				'url' => 'https://youtu.be/abc123',
				'artist' => 'Some Artist',
				'title' => 'Some Title',
			])
			->call('create')
			->assertNotified()
			->assertRedirect();

		$track = Track::where('artist', 'Some Artist')->where('title', 'Some Title')->first();

		$this->assertNotNull($track);
		$this->assertStringEndsWith('.mp3', $track->path);
		$this->assertTrue(Storage::disk('media')->exists($track->path));
		$this->assertEquals(md5_file('./tests/Fixtures/media/test.mp3'), $track->hash);
	}

	public function testShowsReasonAndKeepsFormFilledWhenDownloadFails(): void
	{
		YtDlp::expects('download')
			->once()
			->andThrow(new YtDlpException('ERROR: Video unavailable', 'Failed to download https://youtu.be/abc123 (1)'));

		$response = Livewire::test(CreateTrackFromUrl::class)
			->fillForm([
				'url' => 'https://youtu.be/abc123',
				'artist' => 'Some Artist',
				'title' => 'Some Title',
			])
			->call('create');

		Notification::assertNotified('Download failed');

		$this->assertArrayNotHasKey('redirect', $response->effects);

		$response->assertSchemaStateSet([
			'url' => 'https://youtu.be/abc123',
			'artist' => 'Some Artist',
			'title' => 'Some Title',
		]);

		$this->assertDatabaseMissing(Track::class, [
			'artist' => 'Some Artist',
		]);
	}

	public function testDownloadFailureNotificationIncludesLogDownloadLink(): void
	{
		YtDlp::expects('download')
			->once()
			->andThrow(new YtDlpException('ERROR: Video unavailable', 'Failed to download'));

		Livewire::test(CreateTrackFromUrl::class)
			->fillForm([
				'url' => 'https://youtu.be/abc123',
				'artist' => 'Some Artist',
				'title' => 'Some Title',
			])
			->call('create');

		$component = new Notifications;
		$component->mount();

		$notification = $component->notifications->first(fn (Notification $notification) => $notification->getTitle() === 'Download failed');

		$this->assertNotNull($notification);

		// NB: The log link must be a Notification action rather than an <a> in the body - the body
		// is passed through Filament's HTML sanitizer, which strips href attributes using schemes
		// (like data:) that aren't on its allowlist.
		$action = $notification->getActions()[0];

		$this->assertSame('Download log', $action->getLabel());
		$this->assertSame('yt-dlp-log.txt', $action->getExtraAttributes()['download'] ?? null);
		$this->assertStringContainsString(rawurlencode('ERROR: Video unavailable'), $action->getUrl());
	}

	public function testRejectsExactDuplicateDownload(): void
	{
		$existing = Track::factory()->uploaded('test.mp3')->create();

		$this->fakeSuccessfulDownload('test.mp3');

		Livewire::test(CreateTrackFromUrl::class)
			->fillForm([
				'url' => 'https://youtu.be/abc123',
				'artist' => 'Some Artist',
				'title' => 'Some Title',
			])
			->call('create');

		Notification::assertNotified('Download failed');

		$this->assertDatabaseMissing(Track::class, [
			'artist' => 'Some Artist',
		]);

		$leftovers = collect(Storage::disk('media')->allFiles())
			->reject(fn (string $path) => $path === $existing->path);

		$this->assertCount(0, $leftovers);
	}

	public function testWarnsAboutPossibleDuplicateAfterSuccessfulDownload(): void
	{
		// NB: Tests\Mocks\Olaf::query() always reports a match against 'fake.mp3' regardless of input,
		// so a Track at that path is what makes this scenario resolve to a real duplicate.
		Track::factory()->uploaded(dst: 'fake.mp3')->create();

		$this->fakeSuccessfulDownload();

		Livewire::test(CreateTrackFromUrl::class)
			->fillForm([
				'url' => 'https://youtu.be/abc123',
				'artist' => 'Some Artist',
				'title' => 'Some Title',
			])
			->call('create')
			->assertRedirect();

		Notification::assertNotified('Possible duplicate track found');

		$track = Track::where('artist', 'Some Artist')->first();

		$this->assertNotNull($track);
		$this->assertEquals(1, $track->originals()->count());
	}

	public function testDoesNotWarnWhenNoDuplicateMatchExists(): void
	{
		$this->fakeSuccessfulDownload();

		Livewire::test(CreateTrackFromUrl::class)
			->fillForm([
				'url' => 'https://youtu.be/abc123',
				'artist' => 'Some Artist',
				'title' => 'Some Title',
			])
			->call('create')
			->assertRedirect();

		Notification::assertNotNotified('Possible duplicate track found');
	}
}
