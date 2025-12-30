<?php

use App\Http\Controllers\NightbotController;
use App\Http\Middleware\NightbotAuth;
use App\Http\Middleware\NightbotHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
	return $request->user();
})->middleware('auth:sanctum');

Route::middleware([NightbotAuth::class, NightbotHeaders::class])
	->get('/nightbot/vote', [NightbotController::class, 'vote']);
