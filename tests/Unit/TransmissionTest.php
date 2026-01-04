<?php

namespace Tests\Unit;

use App\Exceptions\TransmissionException;
use App\Facades\Transmission;
use App\Models\Setting;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class TransmissionTest extends TestCase
{
	public function testExpectedSettingsAreRespected(): void
	{
		$data = [
			Setting::BROADCAST_VIDEO_WIDTH => 2345,
			Setting::BROADCAST_VIDEO_HEIGHT => 1234,
			Setting::BROADCAST_BACKGROUND_PATH => 'my-cool-background.png',
			Setting::STREAM_URL => 'rtmp://my-test-server',
			Setting::STREAM_KEY => 'a-very-secret-key',
		];

		Setting::factory()
			->pairs($data)
			->create();

		Process::fake();

		try
		{
			Transmission::begin();
		}
		catch (TransmissionException)
		{
			// NB: Since the fake process won't stay running, do nothing
		}

		Process::assertRan(function (PendingProcess $process, FakeProcessResult $result) {

			$args = new Collection($process->command);

			$dimensions = $args->filter(fn (string $arg) => preg_match('/scale=2345:1234/', $arg))->first();

			$background = $args->filter(fn (string $arg) => preg_match("/my-cool-background\.png$/", $arg))->first();

			return $dimensions !== null &&
				$background !== null &&
				$args->last() === 'rtmp://my-test-server/a-very-secret-key';

		});
	}
}
