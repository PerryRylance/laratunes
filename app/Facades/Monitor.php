<?php

namespace App\Facades;

use App\Services\MonitorService;
use Illuminate\Support\Facades\Facade;

class Monitor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MonitorService::class;
    }
}
