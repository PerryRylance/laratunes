<?php

namespace Tests\Feature;

use App\Exceptions\YtDlpException;
use App\Facades\YtDlp;
use App\Filament\Pages\Settings;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
	public function testCommandSetsExpectedDefaults(): void
	{
		Artisan::call('app:create-default-settings');

		$this->assertEquals(1280, Setting::value(Setting::BROADCAST_VIDEO_WIDTH));
		$this->assertEquals(720, Setting::value(Setting::BROADCAST_VIDEO_HEIGHT));
		$this->assertEquals('default-background.png', Setting::value(Setting::BROADCAST_BACKGROUND_PATH));
		$this->assertEquals('', Setting::value(Setting::STREAM_URL));
		$this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', Setting::value(Setting::NIGHTBOT_API_TOKEN));
	}

	public function testFormIsInitiallyPopulated(): void
	{
		Artisan::call('app:create-default-settings');

		Setting::value(Setting::STREAM_URL, 'rtmp://test.stream');
		Setting::value(Setting::STREAM_KEY, 'my-test-key');

		Storage::disk('media')->put('default-background.png', file_get_contents(resource_path('./media/default-background.png')));

		Livewire::test(Settings::class)
			->assertOk()
			->assertSchemaStateSet(function (array $state) {

				$this->assertEquals(1280, $state[Setting::BROADCAST_VIDEO_WIDTH]);
				$this->assertEquals(720, $state[Setting::BROADCAST_VIDEO_HEIGHT]);
				$this->assertEquals('rtmp://test.stream', $state[Setting::STREAM_URL]);
				$this->assertEquals('my-test-key', $state[Setting::STREAM_KEY]);
				$this->assertEquals('default-background.png', array_first($state[Setting::BROADCAST_BACKGROUND_PATH]));

				// TODO: Nightbot

			});
	}

	public function testCanUpdateVideoDimensions(): void
	{
		Setting::factory()->complete()->create();

		Livewire::test(Settings::class)
			->fillForm([
				Setting::BROADCAST_VIDEO_WIDTH => 1920,
				Setting::BROADCAST_VIDEO_HEIGHT => 1080,
			])
			->call('save')
			->assertOk()
			->assertHasNoErrors()
			->assertNotified();

		$this->assertEquals(1920, Setting::value(Setting::BROADCAST_VIDEO_WIDTH));
		$this->assertEquals(1080, Setting::value(Setting::BROADCAST_VIDEO_HEIGHT));
	}

	public function testCanRotateNightbotApiKey(): void {}

	public function testCanManuallySetBroadcastUrlAndKey(): void
	{
		Setting::factory()->defaults()->create();

		Livewire::test(Settings::class)
			->fillForm([
				Setting::STREAM_URL => 'rtmp://test.stream',
				Setting::STREAM_KEY => 'super-secret',
			])
			->call('save')
			->assertOk()
			->assertHasNoErrors()
			->assertNotified();

		$this->assertEquals('rtmp://test.stream', Setting::value(Setting::STREAM_URL));
	}

	public function testBroadcastUrlMustUseRtmpProtocol(): void
	{
		Setting::factory()->defaults()->create();

		Livewire::test(Settings::class)
			->fillForm([
				Setting::STREAM_URL => 'http://bad.stream',
			])
			->call('save')
			->assertHasErrors([
				'data.'.Setting::STREAM_URL,
			]);

		$this->assertNotEquals('http://bad.stream', Setting::value(Setting::STREAM_URL));
	}

	public function testSeesNotInstalledByDefault(): void
	{
		YtDlp::expects('installed')->andReturn(false);

		Livewire::test(Settings::class)
			->assertSee('Not installed')
			->assertSee('Install')
			->assertDontSee('Upgrade');
	}

	public function testSeesVersionAndUpgradeButtonWhenInstalled(): void
	{
		YtDlp::expects('installed')->andReturn(true);
		YtDlp::expects('version')->andReturn('2024.12.13');
		YtDlp::expects('latestVersion')->andReturn('2024.12.13');

		Livewire::test(Settings::class)
			->assertSee('2024.12.13')
			->assertSee('Upgrade')
			->assertDontSee('Install');
	}

	public function testInstallButtonAsksForConfirmation(): void
	{
		YtDlp::expects('installed')->andReturn(false);

		Livewire::test(Settings::class)
			->assertSeeHtml('wire:confirm=');
	}

	public function testInstallingYtdlpCallsServiceAndNotifies(): void
	{
		YtDlp::shouldReceive('installed')->andReturn(false);
		YtDlp::expects('install')->once();

		Livewire::test(Settings::class)
			->call('installYtdlp')
			->assertOk()
			->assertNotified();
	}

	public function testInstallingYtdlpShowsErrorOnFailure(): void
	{
		YtDlp::shouldReceive('installed')->andReturn(false);
		YtDlp::expects('install')->once()->andThrow(new YtDlpException('boom', 'Failed to install yt-dlp'));

		Livewire::test(Settings::class)
			->call('installYtdlp')
			->assertOk()
			->assertNotified('Failed to install yt-dlp');
	}

	public function testUpgradingYtdlpCallsServiceAndNotifies(): void
	{
		YtDlp::shouldReceive('installed')->andReturn(true);
		YtDlp::shouldReceive('version')->andReturn('2024.12.13');
		YtDlp::shouldReceive('latestVersion')->andReturn('2024.12.13');
		YtDlp::expects('upgrade')->once();

		Livewire::test(Settings::class)
			->call('upgradeYtdlp')
			->assertOk()
			->assertNotified();
	}

	public function testUpgradingYtdlpShowsErrorOnFailure(): void
	{
		YtDlp::shouldReceive('installed')->andReturn(true);
		YtDlp::shouldReceive('version')->andReturn('2024.12.13');
		YtDlp::shouldReceive('latestVersion')->andReturn('2024.12.13');
		YtDlp::expects('upgrade')->once()->andThrow(new YtDlpException('boom', 'Failed to upgrade yt-dlp'));

		Livewire::test(Settings::class)
			->call('upgradeYtdlp')
			->assertOk()
			->assertNotified('Failed to upgrade yt-dlp');
	}
}
