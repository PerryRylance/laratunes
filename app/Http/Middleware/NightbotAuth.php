<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class NightbotAuth
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
	 */
	public function handle(Request $request, Closure $next): Response
	{
		if (! config('nightbot.api_token'))
			throw new Exception('Nightbot API token not configured');

		if (! $request->has('token'))
			throw new BadRequestHttpException('No API token specified');

		if ($request->input('token') !== config('nightbot.api_token'))
			throw new UnauthorizedHttpException('Invalid API token');

		return $next($request);
	}
}
