<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
	/**
	 * Define the model's default state.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		return [
			//
		];
	}

	public function defaults(): Factory
	{
		$data = [
			[
				'name' => Setting::BROADCAST_VIDEO_WIDTH,
				'value' => 1280,
			],
			[
				'name' => Setting::BROADCAST_VIDEO_HEIGHT,
				'value' => 720,
			],
			[
				'name' => Setting::BROADCAST_BACKGROUND_PATH,
				'value' => 'default-background.png',
			],
		];

		return $this
			->state(new Sequence(...$data))
			->count(count($data));
	}
}
