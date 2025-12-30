<?php

namespace App\Facades;

use App\Services\FifoService;
use Illuminate\Support\Facades\Facade;

class Fifo extends Facade
{
	// public static function fake(): void
	// {
	//     static::swap(new \Tests\Mocks\Fifo);
	// }

	protected static function getFacadeAccessor()
	{
		return FifoService::class;
	}
}
