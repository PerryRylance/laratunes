<?php

namespace App\Services;

use App\Exceptions\TransmissionException;
use App\Facades\Ffmpeg;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TransmissionService
{
    public static function begin(): void
    {
		try{

			Log::info("Beginning transmission");

			if(empty(config('broadcast.url')))
				throw new Exception('Broadcast URL not configured');

			static::work();

		}catch(Throwable $e) {

			Log::error("Error transmitting: " . $e->getMessage());
            exit(1);

		}
    }

	private static function work(): void
	{
		[
            'video_width' => $width,
            'video_height' => $height,
            'url' => $url,
            'background' => $background
        ]
            = config('broadcast');

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
            '-ac',
            '2',
            '-af',
            'aresample=resampler=soxr',
            '-async',
            '1',

            // Video encoding and tuning
            '-c:v',
            'libx264',
            '-profile:v',
            'high',
            '-crf',
            '18',
            '-preset',
            'slow',
            '-tune',
            'zerolatency',
            '-g',
            '15',
            '-vsync',
            'passthrough',

            // Buffering / max delay tuning to reduce choppiness
            '-bufsize',
            '2M',
            '-max_delay',
            '500k',

            // Output format for RTMP
            '-f',
            'flv',
            '-flvflags',
            'no_duration_filesize',

            // RTMP URL
            $url
		]);

		while($process->running())
			usleep(500_000);

		throw new TransmissionException($process, 'Transmission stopped unexpectedly (Exit code {$process->exitCode()})');
	}
}
