<?php

namespace Tests\Unit;

use App\Facades\BroadcastSupervisor;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SuperviseBroadcastTest extends TestCase
{
	private function runOnce(): void
	{
		Artisan::call('app:supervise-broadcast', [
			'--once' => true,
		]);
	}

	public function testDoesNothingWhenBroadcastIsFullyRunning(): void
	{
		BroadcastSupervisor::expects('isFullyRunning')->andReturn(true);
		BroadcastSupervisor::shouldNotReceive('start');

		$this->runOnce();
	}

	public function testDoesNotRestartOnFirstDetectionWhenWithinTheGracePeriod(): void
	{
		config(['broadcast.watchdog_grace_period' => 120]);

		BroadcastSupervisor::expects('isFullyRunning')->andReturn(false);
		BroadcastSupervisor::expects('diagnostics')->andReturn([]);
		BroadcastSupervisor::shouldNotReceive('start');

		$this->runOnce();
	}

	public function testRestartsOnceTheGracePeriodHasElapsed(): void
	{
		config(['broadcast.watchdog_grace_period' => 0]);

		BroadcastSupervisor::expects('isFullyRunning')->andReturn(false);
		BroadcastSupervisor::expects('diagnostics')->twice()->andReturn([]);
		BroadcastSupervisor::expects('start')->once();

		$this->runOnce();
	}

	public function testLogsCriticallyWithoutThrowingWhenTheRestartItselfFails(): void
	{
		config(['broadcast.watchdog_grace_period' => 0]);

		BroadcastSupervisor::expects('isFullyRunning')->andReturn(false);
		BroadcastSupervisor::expects('diagnostics')->times(3)->andReturn([]);
		BroadcastSupervisor::expects('start')->andThrow(new \Exception('nope'));

		$this->runOnce();

		$this->assertTrue(true);
	}
}
