<?php

namespace Tests;

use App\Facades\Olaf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\Attributes\UsesRealOlaf;

abstract class TestCase extends BaseTestCase
{
	use RefreshDatabase;

	protected function setUp(): void
	{
		parent::setUp();

		Storage::fake('media');

		$reflect = new ReflectionClass($this);

		if (empty($reflect->getAttributes(UsesRealOlaf::class)))
			Olaf::fake();
	}
}
