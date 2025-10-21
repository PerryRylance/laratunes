<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\NightbotAuth;
use App\Http\Middleware\NightbotHeaders;
use App\Http\Controllers\NightbotController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('/nightbot')
	->middleware([NightbotAuth::class, NightbotHeaders::class])
    ->get('/vote', [NightbotController::class, 'vote']);
