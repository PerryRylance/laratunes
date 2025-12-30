<?php

namespace App\Facades;

use App\Services\NowPlayingService;
use Illuminate\Support\Facades\Facade;

class NowPlaying extends Facade
{
	public static function fake(): void
	{
		static::swap(new \Tests\Mocks\NowPlaying);
	}

	protected static function getFacadeAccessor()
	{
		return NowPlayingService::class;
	}
}
