<?php

namespace App\Services;

use App\Exceptions\YtDlpException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class YtDlpService
{
	public static function installed(): bool
	{
		return Process::run([config('yt-dlp.binary_path'), '--version'])->successful();
	}

	public static function version(): ?string
	{
		$result = Process::run([config('yt-dlp.binary_path'), '--version']);

		if ($result->failed())
			return null;

		return trim($result->output());
	}

	public static function latestVersion(): ?string
	{
		try
		{
			$response = Http::timeout(10)->get(config('yt-dlp.latest_version_url'));

			if ($response->failed())
				return null;

			return $response->json('tag_name');
		}
		catch (Throwable $exception)
		{
			// NB: Fail open - the latest-version check is a nicety, it shouldn't block the settings page if GitHub is unreachable.
			Log::warning('Failed to check latest yt-dlp version: '.$exception->getMessage());

			return null;
		}
	}

	public static function install(): void
	{
		$binary = config('yt-dlp.binary_path');

		$result = Process::timeout(config('yt-dlp.timeout'))->run([
			'curl', '-fsSL', config('yt-dlp.download_url'), '-o', $binary,
		]);

		if ($result->failed())
			throw new YtDlpException($result->errorOutput(), "Failed to install yt-dlp ({$result->exitCode()})");

		$result = Process::run(['chmod', '+x', $binary]);

		if ($result->failed())
			throw new YtDlpException($result->errorOutput(), "Failed to make yt-dlp executable ({$result->exitCode()})");
	}

	public static function upgrade(): void
	{
		$result = Process::timeout(config('yt-dlp.timeout'))->run([config('yt-dlp.binary_path'), '-U']);

		if ($result->failed())
			throw new YtDlpException($result->errorOutput(), "Failed to upgrade yt-dlp ({$result->exitCode()})");
	}

	public static function download(string $url, string $destination): void
	{
		$result = Process::timeout(config('yt-dlp.timeout'))->run([
			config('yt-dlp.binary_path'),
			'-x',
			'--audio-format', 'mp3',
			'-o', $destination,
			$url,
		]);

		if ($result->failed())
			throw new YtDlpException($result->output().$result->errorOutput(), "Failed to download $url ({$result->exitCode()})");
	}
}
