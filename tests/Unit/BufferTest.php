<?php

namespace Tests\Unit;

use App\Facades\Buffer;
use App\Services\BufferService;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class BufferTest extends TestCase
{
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

	public function testRestartKillsTheBufferingProcess(): void
	{
		$this->fakePs();

		Buffer::restart();

		Process::assertRan(fn (PendingProcess $process) => $process->command === ['kill', '-9', '101']);
	}

	public function testRestartDoesNotKillTheTransmissionProcess(): void
	{
		$this->fakePs();

		Buffer::restart();

		Process::assertNotRan(fn (PendingProcess $process) => $process->command === ['kill', '-9', '202']);
	}

	public function testRestartDoesNothingWhenBufferIsNotRunning(): void
	{
		Process::fake([
			'ps *' => Process::result(output: ''),
		])->preventStrayProcesses();

		Buffer::restart();

		Process::assertNotRan(fn (PendingProcess $process) => is_array($process->command) && $process->command[0] === 'kill');
	}

	public function testIsBufferingIsTrueWhenTheBufferingProcessIsRunning(): void
	{
		$this->fakePs();

		$this->assertTrue(Buffer::isBuffering());
	}

	public function testIsBufferingIsFalseWhenNothingIsRunning(): void
	{
		Process::fake([
			'ps *' => Process::result(output: ''),
		])->preventStrayProcesses();

		$this->assertFalse(Buffer::isBuffering());
	}

	public function testIsBufferingIsFalseWhenOnlyTheTransmissionProcessIsRunning(): void
	{
		$path = BufferService::NOW_PLAYING_BUFFER_PATH;

		Process::fake([
			'ps *' => Process::result(output: <<<OUTPUT
			    202 ffmpeg -re -stream_loop -1 -fflags +genpts -i /media/background.mp4 -f mpegts -i $path -filter_complex [0:v][1:v] overlay rtmp://stream.example/key
			OUTPUT),
		])->preventStrayProcesses();

		$this->assertFalse(Buffer::isBuffering());
	}
}
