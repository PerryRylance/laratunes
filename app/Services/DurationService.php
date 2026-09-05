<?php

namespace App\Services;

use App\Facades\Ffmpeg;
use App\Models\Track;
use Illuminate\Support\Facades\Cache;

class DurationService
{
	const CACHE_KEY = 'tracks:total_duration';

	public static function getDurationFromFile(string $filename): int
	{
		return Ffmpeg::getDuration($filename);
	}

	public static function getTracksTotalDuration(): int
	{
		return (int) Cache::rememberForever(self::CACHE_KEY, fn () => Track::sum('duration'));
	}

	public static function invalidateCachedTrackTotalDuration(): void
	{
		Cache::forget(self::CACHE_KEY);
	}
}
