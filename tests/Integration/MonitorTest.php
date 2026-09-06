<?php

namespace Tests\Integration;

use App\Facades\Monitor;
use App\Services\BufferService;
use DateTime;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;
use UnexpectedValueException;

class MonitorTest extends TestCase
{
	private function runCommand(): void
	{
		Artisan::call('app:monitor', [
			'--once' => true,
		]);
	}

	private function filterCommandsByKey(string $key, array $commands): array
	{
		return array_values(array_filter($commands, fn ($command) => $command[1] === $key));
	}

	private function assertMetricHistory(string $key, callable $assertValue): void
	{
		$commands = [];

		// NB: Mock this out because Redis is used as a store for ffmpeg bitrates
		Redis::shouldReceive('get')->andReturnUsing(function ($key) {
			switch ($key)
			{
				case 'monitor:bitrate:transmitter':
					return 1000;

				default:
					throw new UnexpectedValueException();
			}
		});

		// NB: Mock this out for the updated_at just so there is a method there, even though we test this separately
		Redis::shouldReceive('set');

		Redis::shouldReceive('pipeline')
			->andReturnUsing(function ($callback) use (&$commands) {

				$mockPipe = Mockery::mock();

				foreach (['lpush', 'ltrim'] as $verb)
					$mockPipe
						->shouldReceive($verb)
						->once()
						->andReturnUsing(function ($key, ...$args) use (&$commands, $verb) {
							$commands[] = [$verb, $key, $args];
						});

				$callback($mockPipe);

			});

		$this->runCommand();

		$commands = $this->filterCommandsByKey($key, $commands);

		$this->assertCount(2, $commands);

		$this->assertEquals($commands[0][0], 'lpush');
		$this->assertEquals($commands[0][1], $key);

		$assertValue($commands[0][2][0]);

		$this->assertEquals($commands[1][0], 'ltrim');
		$this->assertEquals($commands[1][1], $key);
		$this->assertEquals($commands[1][2], [0, 29]);
	}

	public function testCpuUsage(): void
	{
		$this->assertMetricHistory('monitor:cpu', function ($percent) {

			$this->assertIsFloat($percent);
			$this->assertGreaterThanOrEqual(0, $percent);
			$this->assertLessThanOrEqual(100, $percent);

		});
	}

	public function testMemoryUsage(): void
	{
		$this->assertMetricHistory('monitor:memory', function ($usage) {

			$this->assertIsFloat($usage);
			$this->assertGreaterThan(0, $usage);

		});
	}

	public function testFifoBytesAvailable(): void
	{
		unlink(BufferService::NOW_PLAYING_BUFFER_PATH);

		if (posix_mkfifo(BufferService::NOW_PLAYING_BUFFER_PATH, 0666) === false)
			$this->fail(posix_strerror(posix_get_last_error()));

		$fh = fopen(BufferService::NOW_PLAYING_BUFFER_PATH, 'w+');

		if ($fh === false)
			$this->fail('Failed to open file');

		stream_set_blocking($fh, false);
		fwrite($fh, 'test');

		$this->assertMetricHistory('monitor:buffer', fn ($bytes) => $this->assertEquals(4, $bytes));
	}

	public function testUpdatedAt(): void
	{
		Redis::shouldReceive('pipeline');
		Redis::shouldReceive('get');

		Redis::shouldReceive('set')
			->andReturnUsing(function ($key, $value) {

				if ($key === 'monitor:memory_total')
					return 16384.0;

				$this->assertEquals($key, 'monitor:updated_at');

				$updatedAt = DateTime::createFromFormat(DateTime::ATOM, $value);
				$now = new DateTime;

				$diff = $now->getTimestamp() - $updatedAt->getTimestamp();

				$this->assertLessThanOrEqual(3, $diff);

			});

		$this->runCommand();
	}

	public function testHistoryDoesntExceedLimit(): void
	{
		for ($i = 0; $i < 31; $i++)
			$this->runCommand();

		foreach ([
			'monitor:cpu',
			'monitor:memory',
			'monitor:buffer',
		] as $key)
			$this->assertCount(30, Monitor::list($key));
	}

	public function testIsRunningIsFalseWhenNeverUpdated(): void
	{
		Redis::shouldReceive('get')
			->with('monitor:updated_at')
			->andReturn(null);

		$this->assertFalse(Monitor::isRunning());
	}

	public function testIsRunningIsTrueWhenRecentlyUpdated(): void
	{
		Redis::shouldReceive('get')
			->with('monitor:updated_at')
			->andReturn((new DateTime)->format(DateTime::ATOM));

		$this->assertTrue(Monitor::isRunning());
	}

	public function testIsRunningIsFalseWhenLastUpdateIsStale(): void
	{
		Redis::shouldReceive('get')
			->with('monitor:updated_at')
			->andReturn((new DateTime)->modify('-1 hour')->format(DateTime::ATOM));

		$this->assertFalse(Monitor::isRunning());
	}

	public function testNotifyOnHighCpu(): void {}

	public function testNotifyOnHighMemory(): void {}

	public function testNotifyOnBufferUnderrun(): void {}
}
