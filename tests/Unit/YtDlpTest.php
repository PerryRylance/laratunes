<?php

namespace Tests\Unit;

use App\Exceptions\YtDlpException;
use App\Facades\YtDlp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class YtDlpTest extends TestCase
{
	public function testInstalledIsTrueWhenBinaryRuns(): void
	{
		Process::fake([
			'*' => Process::result(output: 'yt-dlp 2024.12.13'),
		]);

		$this->assertTrue(YtDlp::installed());
	}

	public function testInstalledIsFalseWhenBinaryMissing(): void
	{
		Process::fake([
			'*' => Process::result(exitCode: 127),
		]);

		$this->assertFalse(YtDlp::installed());
	}

	public function testVersionReturnsTrimmedOutput(): void
	{
		Process::fake([
			'*' => Process::result(output: "2024.12.13\n"),
		]);

		$this->assertEquals('2024.12.13', YtDlp::version());
	}

	public function testVersionReturnsNullWhenNotInstalled(): void
	{
		Process::fake([
			'*' => Process::result(exitCode: 127),
		]);

		$this->assertNull(YtDlp::version());
	}

	public function testInstallDownloadsBinaryAndMakesItExecutable(): void
	{
		Process::fake()->preventStrayProcesses();

		YtDlp::install();

		Process::assertRan(fn ($process) => $process->command[0] === 'curl'
			&& in_array(config('yt-dlp.download_url'), $process->command)
			&& in_array(config('yt-dlp.binary_path'), $process->command));

		Process::assertRan(fn ($process) => $process->command === ['chmod', '+x', config('yt-dlp.binary_path')]);
	}

	public function testInstallThrowsWhenCurlFails(): void
	{
		Process::fake([
			'*curl*' => Process::result(errorOutput: 'curl: could not resolve host', exitCode: 6),
		])->preventStrayProcesses();

		try
		{
			YtDlp::install();
			$this->fail('Expected YtDlpException was not thrown');
		}
		catch (YtDlpException $exception)
		{
			$this->assertStringContainsString('could not resolve host', $exception->output);
		}

		Process::assertNotRan(fn ($process) => $process->command[0] === 'chmod');
	}

	public function testInstallThrowsWhenChmodFails(): void
	{
		Process::fake([
			'*curl*' => Process::result(),
			'*chmod*' => Process::result(errorOutput: 'chmod: no such file', exitCode: 1),
		])->preventStrayProcesses();

		$this->expectException(YtDlpException::class);

		YtDlp::install();
	}

	public function testUpgradeRunsSelfUpdate(): void
	{
		Process::fake()->preventStrayProcesses();

		YtDlp::upgrade();

		Process::assertRan(fn ($process) => $process->command === [config('yt-dlp.binary_path'), '-U']);
	}

	public function testUpgradeThrowsOnFailure(): void
	{
		Process::fake([
			'*' => Process::result(errorOutput: 'network error', exitCode: 1),
		])->preventStrayProcesses();

		try
		{
			YtDlp::upgrade();
			$this->fail('Expected YtDlpException was not thrown');
		}
		catch (YtDlpException $exception)
		{
			$this->assertStringContainsString('network error', $exception->output);
		}
	}

	public function testDownloadRunsYtDlpWithExpectedArguments(): void
	{
		Process::fake()->preventStrayProcesses();

		YtDlp::download('https://youtu.be/abc123', '/media/track.%(ext)s');

		Process::assertRan(fn ($process) => $process->command === [
			config('yt-dlp.binary_path'),
			'-x',
			'--audio-format', 'mp3',
			'-o', '/media/track.%(ext)s',
			'https://youtu.be/abc123',
		]);
	}

	public function testDownloadThrowsWithCapturedOutputOnFailure(): void
	{
		Process::fake([
			'*' => Process::result(
				output: 'ERROR: [youtube] abc123: Video unavailable',
				errorOutput: 'some stderr',
				exitCode: 1,
			),
		])->preventStrayProcesses();

		try
		{
			YtDlp::download('https://youtu.be/abc123', '/media/track.%(ext)s');
			$this->fail('Expected YtDlpException was not thrown');
		}
		catch (YtDlpException $exception)
		{
			$this->assertStringContainsString('Video unavailable', $exception->output);
			$this->assertStringContainsString('some stderr', $exception->output);
		}
	}

	public function testLatestVersionReturnsTagFromGithub(): void
	{
		Http::fake([
			config('yt-dlp.latest_version_url') => Http::response(['tag_name' => '2024.12.13']),
		]);

		$this->assertEquals('2024.12.13', YtDlp::latestVersion());
	}

	public function testLatestVersionReturnsNullOnFailure(): void
	{
		Http::fake([
			config('yt-dlp.latest_version_url') => Http::response(status: 500),
		]);

		$this->assertNull(YtDlp::latestVersion());
	}

	public function testLatestVersionReturnsNullWhenUnreachable(): void
	{
		Http::fake(function () {
			throw new \Illuminate\Http\Client\ConnectionException('Could not resolve host');
		});

		$this->assertNull(YtDlp::latestVersion());
	}
}
