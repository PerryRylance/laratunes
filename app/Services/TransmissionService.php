<?php

namespace App\Services;

use App\Exceptions\ConfigurationException;
use App\Exceptions\TransmissionException;
use App\Facades\Ffmpeg;
use App\Models\Setting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TransmissionService
{
	public static function begin(): void
	{
		try
		{

			Log::info('Beginning transmission');

			if (! Setting::isFullyConfigured())
				throw new ConfigurationException("$required not configured");

			static::work();

		}
		catch (Throwable $e)
		{

			if (App::runningUnitTests())
				throw $e;

			Log::error('Error transmitting: '.$e->getMessage());
			exit(1);

		}
	}

	private static function work(): void
	{
		$width = Setting::value(Setting::BROADCAST_VIDEO_WIDTH);
		$height = Setting::value(Setting::BROADCAST_VIDEO_HEIGHT);
		$background = Setting::value(Setting::BROADCAST_BACKGROUND_PATH);
		$url = Setting::value(Setting::STREAM_URL);
		$key = Setting::value(Setting::STREAM_KEY);

		$process = Ffmpeg::start('Transmitter', priority: -10, params: [
			// Read input in real-time to avoid bursts
			'-re',

			// Verbose logs for debugging
			'-loglevel',
			'debug',

			// Loop the video indefinitely
			'-stream_loop',
			'-1',

			// Generate timestamps to avoid issues with looping
			'-fflags',
			'+genpts',

			// Background video input
			'-i',
			Storage::disk('media')->path($background),

			// Overlay video input (now playing)
			'-f',
			BufferService::NOW_PLAYING_BUFFER_FORMAT,
			'-i',
			BufferService::NOW_PLAYING_BUFFER_PATH,

			// Filter complex: overlay now playing video onto background
			'-filter_complex',
			"[0:v]scale={$width}:{$height}[bg];[1:v]colorkey=0x00FFFF:0.3:0.1[fg];[bg][fg]overlay=0:0[video]",

			// Map only the main video output
			'-map',
			'[video]',

			// Map audio from overlay video
			'-map',
			'1:a',

			// Audio encoding and sync
			'-c:a',
			'aac',
			'-b:a',
			'384k',
			'-ar',
			'48000', // NB: YouTube expects 48kHz
			'-ac',
			'2',
			'-af',
			'volume=-1dB,aresample=resampler=soxr',
			// '-async',
			// '1',

			// Video encoding and tuning
			'-c:v',
			'libx264',
			'-profile:v',
			'high',
			'-bf',
			'2',
			'-crf',
			'18',
			'-pix_fmt',
			'yuv420p',
			// '-b:v',
			// '2500k', // NB: YouTube's recommendation, seems to be at odds with the crf though
			// '-b:v',
			// '10M', // NB: Recommended for 1080p, probably overkill for this

			// NB: Recommended color space, breaks stream
			// '-vf',
			// 'scale=out_color_matrix=bt709',
			// '-color_primaries',
			// 'bt709',
			// '-color_trc bt709',
			// '-colorspace bt709',

			'-coder',
			'1',
			'-preset',
			'slow',
			// '-tune',
			// 'zerolatency',
			// NB: 30fps is suggested here https://www.reddit.com/r/ffmpeg/comments/r1qwyy/best_streaming_settings_for_youtube/?rdt=49142 but this breaks the stream
			// '-r',
			// '30',
			'-g',
			'60', // NB: YouTube wants a keyframe every 2 seconds
			// '-vsync',
			// 'passthrough',
			'-movflags',
			'+faststart',

			// Buffering / max delay tuning to reduce choppiness
			'-bufsize',
			'2M',
			'-max_delay',
			'500k',

			// Performance tweaks
			'-threads',
			'4',
			// '-cpu-used',
			// '0',

			// Output format for RTMP
			'-f',
			'flv',
			'-flvflags',
			'no_duration_filesize',

			// RTMP URL
			$url.'/'.$key,
		]);

		while ($process->running())
		{
			usleep(500_000);
		}

		throw new TransmissionException($process, 'Transmission stopped unexpectedly (Exit code {$process->exitCode()})');
	}

	public static function running(): bool
	{
		return preg_match('/ffmpeg.+\/buffers\/now-playing/ms', shell_exec('ps -eo pid,user,args --width 1000'));
	}
}
