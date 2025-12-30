<?php

namespace Tests\Feature;

class NightbotTestOptions
{
	public function __construct(
		public readonly bool $removeConfigApiToken = false,
		public readonly array|false $nightbotHeaderFields = ['displayName' => 'test user'],
		public readonly string|false|null $queryParamToken = null
	) {}
}
