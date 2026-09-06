<?php

namespace App\Services;

use App\Exceptions\BufferException;
use App\Facades\Ffmpeg;
use App\Models\Setting;
use App\Models\Track;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BufferService
{
	// NB: Not configurable as it's baked into the C source
	const NOW_PLAYING_BUFFER_PATH = '/buffers/now-playing';

	const NOW_PLAYING_BUFFER_FORMAT = 'mpegts';

	const NOW_PLAYING_QR_CODE_PATH = '/tmp/now-playing-qr-code.png';

	const NOW_PLAYING_CAPTION_PATH = '/tmp/now-playing.txt';

	public static function loop(): void
	{
		Log::info('Entering buffer loop');

		if (! Setting::isFullyConfigured())
		{
			Log::error('Settings are not fully configured. Please ensure you have set in video dimensions, a background, stream URL and key in the settings panel');

			return;
		}

		while (true)
		{
			try
			{
				static::bufferNextTrack();
			}
			catch (Throwable $e)
			{
				Log::error('Error buffering: '.$e->getMessage());

				$handler = new Handler(app());
				$handler->report($e);

				if (RateLimiter::tooManyAttempts('buffer-next-track', 10))
					exit(1);

				RateLimiter::hit('buffer-next-track', 60);
			}
		}
	}

	private static function writeCaptionFile(Track $track): void
	{
		file_put_contents(static::NOW_PLAYING_CAPTION_PATH, $track->caption);
	}

	private static function writeQrCode(Track $track): void
	{
		$qrcode = new QRCode(new QROptions([
			'eccLevel' => EccLevel::L,
			'outputType' => QROutputInterface::GDIMAGE_PNG,
		]));

		$qrcode->render($track->url, static::NOW_PLAYING_QR_CODE_PATH);
	}

	private static function bufferNextTrack(): void
	{
		$track = Track::next();

		Log::info("Playing {$track->path}");

		static::writeCaptionFile($track);
		static::writeQrCode($track);

		$width = Setting::value(Setting::BROADCAST_VIDEO_WIDTH);
		$height = Setting::value(Setting::BROADCAST_VIDEO_HEIGHT);

		$file = Storage::disk('media')->path($track->path);
		$caption = static::NOW_PLAYING_CAPTION_PATH;

		// TODO: Review, may be able to remove? The initial call wasn't working but this seems to work well
		Process::run('set_fifo_size');

		$process = Ffmpeg::start('Bufferer', [
			'-y',

			'-i',
			$file,

			'-f',
			'lavfi',

			'-i',
			"color=c=0x00FFFF:s={$width}x{$height}:r=30",

			'-i',
			static::NOW_PLAYING_QR_CODE_PATH,

			'-filter_complex',
			"[1:v][2:v] overlay=x=10:y=H-h-10,format=yuv420p [qr];[qr] drawtext=textfile='{$caption}':fontfile='LiberationSans-Regular.ttf':fontcolor=white:fontsize=24:borderw=2:bordercolor=black:x=10:y=10,format=yuv420p",

			// TODO: Might be able to remove these now we're using MPEG-TS
			'-movflags',
			'frag_keyframe+empty_moov',

			'-shortest',

			'-preset',
			'ultrafast',

			'-f',
			static::NOW_PLAYING_BUFFER_FORMAT,

			static::NOW_PLAYING_BUFFER_PATH,
		]);

		$result = $process->wait();

		if ($result->failed())
			throw new BufferException($process, "Failed to buffer $file ({$result->exitCode()})");
	}

	// NB: The buffer's ffmpeg process writes to the fifo, so it's the one whose command line
	// ends with the buffer path.
	private static function processPattern(): string
	{
		return '/^\s*(\d+)\s+ffmpeg\b.*'.preg_quote(static::NOW_PLAYING_BUFFER_PATH, '/').'\s*$/';
	}

	// NB: Killing it here is enough - the loop() above will catch the resulting failure and move
	// on to buffering the next track
	public static function restart(): void
	{
		Log::info('Restarting buffer');

		Ffmpeg::stop(static::processPattern());
	}

	public static function isBuffering(): bool
	{
		return Ffmpeg::isRunning(static::processPattern());
	}
}
