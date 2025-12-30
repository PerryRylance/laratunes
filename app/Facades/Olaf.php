<?php

namespace App\Facades;

use App\Services\OlafService;
use Illuminate\Support\Facades\Facade;

class Olaf extends Facade
{
	public static function fake(): void
	{
		static::swap(new \Tests\Mocks\Olaf);
	}

	protected static function getFacadeAccessor()
	{
		return OlafService::class;
	}
}
