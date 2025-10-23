<?php

namespace App\Facades;

use App\Services\TransmissionService;
use Illuminate\Support\Facades\Facade;

class Transmission extends Facade
{
    // public static function fake(): void
    // {
    //     static::swap(new \Tests\Mocks\Olaf);
    // }

    protected static function getFacadeAccessor()
    {
        return TransmissionService::class;
    }
}
