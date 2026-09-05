<?php

namespace App\Facades;

use App\Services\DurationService;
use Illuminate\Support\Facades\Facade;

class Duration extends Facade
{
	protected static function getFacadeAccessor()
	{
		return DurationService::class;
	}
}
