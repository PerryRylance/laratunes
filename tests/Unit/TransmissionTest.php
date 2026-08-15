<?php

namespace Tests\Unit;

use App\Exceptions\TransmissionException;
use App\Facades\Transmission;
use App\Models\Setting;
use App\Services\BufferService;
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

	private function fakePs(): void
	{
		$path = BufferService::NOW_PLAYING_BUFFER_PATH;

		Process::fake([
			'ps *' => Process::result(output: <<<OUTPUT
			    101 ffmpeg -y -i /media/track.mp3 -f lavfi -i color=c=0x00FFFF:s=1920x1080:r=30 -i /tmp/now-playing-qr-code.png -filter_complex [1:v][2:v] overlay -f mpegts $path
			    202 ffmpeg -re -stream_loop -1 -fflags +genpts -i /media/background.mp4 -f mpegts -i $path -filter_complex [0:v][1:v] overlay rtmp://stream.example/key
			OUTPUT),
			'*kill*' => Process::result(),
		])->preventStrayProcesses();
	}

	public function testRestartKillsTheTransmissionProcess(): void
	{
		$this->fakePs();

		Transmission::restart();

		Process::assertRan(fn (PendingProcess $process) => $process->command === ['kill', '-9', '202']);
	}

	public function testRestartDoesNotKillTheBufferingProcess(): void
	{
		$this->fakePs();

		Transmission::restart();

		Process::assertNotRan(fn (PendingProcess $process) => $process->command === ['kill', '-9', '101']);
	}
}
