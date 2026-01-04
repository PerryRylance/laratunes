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

	public function pairs(array $pairs): Factory
	{
		$keys = array_keys($pairs);
		$values = array_values($pairs);
		$data = [];

		for ($i = 0; $i < count($pairs); $i++)
			$data[] = [
				'name' => $keys[$i],
				'value' => $values[$i],
			];

		return $this
			->state(new Sequence(...$data))
			->count(count($data));
	}

	public function defaults(): Factory
	{
		return $this->pairs([
			Setting::BROADCAST_VIDEO_WIDTH => 1280,
			Setting::BROADCAST_VIDEO_HEIGHT => 720,
			Setting::BROADCAST_BACKGROUND_PATH => 'default-background.png',
		]);
	}

	public function complete(): Factory
	{
		return $this->pairs([
			Setting::BROADCAST_VIDEO_WIDTH => 1280,
			Setting::BROADCAST_VIDEO_HEIGHT => 720,
			Setting::BROADCAST_BACKGROUND_PATH => 'default-background.png',
			Setting::STREAM_URL => 'rtmp://some.endpoint',
			Setting::STREAM_KEY => 'my-key',
		]);
	}
}
