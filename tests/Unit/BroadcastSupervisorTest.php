<?php

namespace Tests\Unit;

use App\Exceptions\BroadcastSupervisorException;
use App\Facades\BroadcastSupervisor;
use App\Facades\Buffer;
use App\Facades\Monitor;
use App\Facades\Transmission;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class BroadcastSupervisorTest extends TestCase
{
	private function fakePs(): void
	{
		Process::fake([
			'ps *' => Process::result(output: <<<'OUTPUT'
			    555 php artisan app:start-broadcast
			    556 php artisan app:start-broadcast
			OUTPUT),
			'*kill*' => Process::result(),
			'*nohup*' => Process::result(),
		])->preventStrayProcesses();
	}

	public function testStopKillsEveryProcessMatchingStartBroadcast(): void
	{
		$this->fakePs();

		Buffer::expects('restart')->once();
		Transmission::expects('restart')->once();

		BroadcastSupervisor::stop();

		Process::assertRan(fn (PendingProcess $process) => $process->command === ['kill', '-9', '555']);
		Process::assertRan(fn (PendingProcess $process) => $process->command === ['kill', '-9', '556']);
	}

	public function testStopDoesNothingWhenNoSupervisorIsRunning(): void
	{
		Process::fake([
			'ps *' => Process::result(output: ''),
		])->preventStrayProcesses();

		Buffer::expects('restart')->once();
		Transmission::expects('restart')->once();

		BroadcastSupervisor::stop();

		Process::assertNotRan(fn (PendingProcess $process) => is_array($process->command) && $process->command[0] === 'kill');
	}

	public function testIsFullyRunningIsTrueOnlyWhenAllThreeProcessesAreRunning(): void
	{
		Buffer::expects('isBuffering')->andReturn(true);
		Transmission::expects('isTransmitting')->andReturn(true);
		Monitor::expects('isRunning')->andReturn(true);

		$this->assertTrue(BroadcastSupervisor::isFullyRunning());
	}

	public function testIsFullyRunningIsFalseWhenBufferingIsMissing(): void
	{
		Buffer::expects('isBuffering')->andReturn(false);
		Transmission::shouldReceive('isTransmitting')->andReturn(true);
		Monitor::shouldReceive('isRunning')->andReturn(true);

		$this->assertFalse(BroadcastSupervisor::isFullyRunning());
	}

	public function testIsFullyRunningIsFalseWhenTransmittingIsMissing(): void
	{
		Buffer::expects('isBuffering')->andReturn(true);
		Transmission::expects('isTransmitting')->andReturn(false);
		Monitor::shouldReceive('isRunning')->andReturn(true);

		$this->assertFalse(BroadcastSupervisor::isFullyRunning());
	}

	public function testIsFullyRunningIsFalseWhenMonitoringIsMissing(): void
	{
		Buffer::expects('isBuffering')->andReturn(true);
		Transmission::expects('isTransmitting')->andReturn(true);
		Monitor::expects('isRunning')->andReturn(false);

		$this->assertFalse(BroadcastSupervisor::isFullyRunning());
	}

	public function testStartStopsFirstLaunchesTheSupervisorAndWaitsForItToComeUp(): void
	{
		$this->fakePs();

		Buffer::expects('restart')->once();
		Transmission::expects('restart')->once();

		Buffer::expects('isBuffering')->andReturn(true);
		Transmission::expects('isTransmitting')->andReturn(true);
		Monitor::expects('isRunning')->andReturn(true);

		BroadcastSupervisor::start();

		Process::assertRan(fn (PendingProcess $process) => is_string($process->command) && str_contains($process->command, 'app:start-broadcast') && str_contains($process->command, 'nohup'));
	}

	public function testStartThrowsWhenTheSupervisorNeverFullyComesUp(): void
	{
		config(['broadcast.startup_timeout' => 0]);

		$this->fakePs();

		Buffer::expects('restart')->once();
		Transmission::expects('restart')->once();

		Buffer::expects('isBuffering')->andReturn(true);
		Transmission::expects('isTransmitting')->andReturn(false);
		Monitor::expects('isRunning')->andReturn(true);

		$this->expectException(BroadcastSupervisorException::class);

		BroadcastSupervisor::start();
	}
}
