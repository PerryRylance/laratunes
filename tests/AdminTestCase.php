<?php

namespace Tests;

use App\Models\User;

class AdminTestCase extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->actingAs(User::factory()->admin()->create());
	}
}
