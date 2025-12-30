<?php

namespace App\Facades;

use App\Services\BufferService;
use Illuminate\Support\Facades\Facade;

class Buffer extends Facade
{
	// public static function fake(): void
	// {
	//     static::swap(new \Tests\Mocks\Buffer);
	// }

	protected static function getFacadeAccessor()
	{
		return BufferService::class;
	}
}
