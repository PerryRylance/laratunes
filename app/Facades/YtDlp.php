<?php

namespace App\Facades;

use App\Services\YtDlpService;
use Illuminate\Support\Facades\Facade;

class YtDlp extends Facade
{
	protected static function getFacadeAccessor()
	{
		return YtDlpService::class;
	}
}
