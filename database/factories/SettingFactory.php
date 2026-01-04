<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Collection;

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

	public function getDefaultPairs(): Collection
	{
		return new Collection([
			Setting::BROADCAST_VIDEO_WIDTH => 1280,
			Setting::BROADCAST_VIDEO_HEIGHT => 720,
			Setting::BROADCAST_BACKGROUND_PATH => 'default-background.png',
			Setting::STREAM_URL => 'rtmp://some.endpoint',
			Setting::STREAM_KEY => 'my-key',
		]);
	}

	public function pairs(array|Collection $pairs): Factory
	{
		if (! ($pairs instanceof Collection))
			$pairs = new Collection($pairs);

		$keys = $pairs->keys();
		$values = $pairs->values();
		$data = [];

		for ($i = 0; $i < $pairs->count(); $i++)
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
		return $this->pairs(
			static::getDefaultPairs()->except([
				Setting::STREAM_URL,
				Setting::STREAM_KEY,
			])
		);
	}

	public function complete(): Factory
	{
		return $this->pairs(static::getDefaultPairs());
	}
}
