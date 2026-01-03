<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
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
		$this->assertEquals('', Setting::value(Setting::BROADCAST_URL));
		$this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', Setting::value(Setting::NIGHTBOT_API_TOKEN));
	}

	public function testFormIsInitiallyPopulated(): void
	{
		Artisan::call('app:create-default-settings');

		Setting::value(Setting::BROADCAST_URL, 'rtmp://test.stream');

		Livewire::test(Settings::class)
			->assertSeeHtml(
				Arr::map([
					1280,
					720,
					'rtmp://test.stream',
					Setting::value(Setting::NIGHTBOT_API_TOKEN),
					'default-background.png',
				],
					fn (string $value) => "value=\"$value\""
				));
	}

	public function testCanUpdateVideoDimensions(): void
	{
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

	public function testCanManuallySetBroadcastUrl(): void
	{
		Setting::factory()->defaults()->create();

		Livewire::test(Settings::class)
			->fillForm([
				Setting::BROADCAST_URL => 'rtmp://test.stream',
			])
			->call('save')
			->assertOk()
			->assertHasNoErrors()
			->assertNotified();

		$this->assertEquals('rtmp://test.stream', Setting::value(Setting::BROADCAST_URL));
	}

	public function testBroadcastUrlMustUseRtmpProtocol(): void
	{
		Setting::factory()->defaults()->create();

		Livewire::test(Settings::class)
			->fillForm([
				Setting::BROADCAST_URL => 'http://bad.stream',
			])
			->call('save')
			->assertHasErrors([
				Setting::BROADCAST_URL,
			]);

		$this->assertNotEquals('http://bad.stream', Setting::value(Setting::BROADCAST_URL));
	}
}
