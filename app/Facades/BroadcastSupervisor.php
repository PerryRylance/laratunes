<?php

namespace App\Facades;

use App\Services\BroadcastSupervisorService;
use Illuminate\Support\Facades\Facade;

class BroadcastSupervisor extends Facade
{
	protected static function getFacadeAccessor()
	{
		return BroadcastSupervisorService::class;
	}
}
