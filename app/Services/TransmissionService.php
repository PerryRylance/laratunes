<?php

namespace App\Services;

use App\Facades\Ffmpeg;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TransmissionService
{
    public static function begin(): void
    {
		Log::info("Beginning transmission");

		if(empty(config('broadcast.url')))
            throw new Exception('Broadcast URL not configured');

        [
            'video_width' => $width,
            'video_height' => $height,
            'url' => $url,
            'background' => $background
        ]
            = config('broadcast');

        $process = Ffmpeg::start('Transmitter', [
            // NB: Broadcast in real time to avoid choking YouTube
			'-re',

			// '-loglevel',
			// 'verbose',

			'-stream_loop',
			'-1',

			// NB: Generate timestamps to avoid issues with looping video
			'-fflags',
			'+genpts',

			'-i',
			Storage::disk('media')->path($background),

            '-f',
            BufferService::NOW_PLAYING_BUFFER_FORMAT,
            '-i',
            BufferService::NOW_PLAYING_BUFFER_PATH,

			// NB: Add the now playing video over our video loop
			'-filter_complex',
			"[0:v]scale={$width}:{$height}[bg];[1:v]colorkey=0x00FFFF:0.3:0.1[fg];[bg][fg]overlay=0:0[video];[video]split=2[v1][v2];[v2]fps=0.25[screenshot]",

			'-map',
			'[v1]',

			'-map',
			'1:a',

			'-c:a',
			'aac',
			'-b:a',
			'192k',

			// NB: Codec and bitrate
			'-c:v',
			'libx264',

			// '-b:v',
			// '2500k',

			// NB: Tune for YouTube
			'-crf',
			'23',
			'-preset',
			'veryfast',
			'-tune',
			'zerolatency',
			'-g',
			'60',

            '-f',
            'flv',

			$url,

			'-map',
			'[screenshot]',

			'-f',
			'image2',
			'-update',
			'1',
			'-y',
			'screenshot.jpg'
		]);

		// NB: Give it a second to start
		usleep(500_000);

		if(!$process->running())
			throw new Exception("Process stopped running unexpectedly");
    }
}
