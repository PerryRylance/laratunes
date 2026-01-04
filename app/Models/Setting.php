<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Random\Randomizer;

class Setting extends Model
{
	use HasFactory;

	const STREAM_URL = 'stream-url';

	const STREAM_KEY = 'stream-key';

	const BROADCAST_VIDEO_WIDTH = 'broadcast-video-width';

	const BROADCAST_VIDEO_HEIGHT = 'broadcast-video-height';

	const BROADCAST_BACKGROUND_PATH = 'broadcast-background-path';

	const NIGHTBOT_API_TOKEN = 'nightbot-api-token';

	protected $fillable = [
		'name',
		'value',
	];

	public static function generateApiToken(): string
	{
		return bin2hex(new Randomizer()->getBytes(16));
	}

	public static function isFullyConfigured(): bool
	{
		foreach ([
			Setting::BROADCAST_VIDEO_WIDTH,
			Setting::BROADCAST_VIDEO_HEIGHT,
			Setting::BROADCAST_BACKGROUND_PATH,
			Setting::STREAM_URL,
			Setting::STREAM_KEY,
		] as $required)
			if (empty(Setting::value($required)))
				return false;

		return true;
	}

	public static function value(string $name, $value = null): string|int|bool|null
	{
		$setting = Setting::whereName($name)->first();

		if ($value)
		{
			if ($setting === null)
				Setting::create([
					'name' => $name,
					'value' => $value,
				]);
			else $setting->update([
				'value' => $value,
			]);

			return true;
		}
		else
		{
			$result = $setting?->value;

			switch ($name)
			{
				case static::BROADCAST_VIDEO_WIDTH:
				case static::BROADCAST_VIDEO_HEIGHT:
					return (int) $result;
			}

			return $result;
		}
	}

	public static function getAllAsAssociativeArray(): array
	{
		$settings = static::get();
		$result = [];

		foreach ($settings as $setting)
			$result[$setting->name] = $setting->value;

		return $result;
	}
}
