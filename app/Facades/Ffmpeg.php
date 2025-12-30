<?php

namespace App\Facades;

use App\Services\FfmpegService;
use Illuminate\Support\Facades\Facade;

class Ffmpeg extends Facade
{
	// public static function fake(): void
	// {
	//     static::swap(new \Tests\Mocks\Ffmpeg);
	// }

	protected static function getFacadeAccessor()
	{
		return FfmpegService::class;
	}
}
