<?php

namespace App\Services;

use App\Exceptions\BufferException;
use App\Facades\Fifo;
use App\Models\Track;
use Illuminate\Support\Facades\Log;
use App\Facades\Ffmpeg;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Fiber;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Laravel\Prompts\Output\ConsoleOutput;
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
        try{

            Log::info("Creating FIFO buffer");
            Fifo::create(static::NOW_PLAYING_BUFFER_PATH);

            while(true)
                static::bufferNextTrack();

        }catch(Throwable $e) {

            Log::error("Error buffering: " . $e->getMessage());
            exit(1);

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
            'outputType' => QROutputInterface::GDIMAGE_PNG
        ]));

        // TODO: This needs to be the URL not just the hash

        $qrcode->render($track->hash, static::NOW_PLAYING_QR_CODE_PATH);
    }

    private static function bufferNextTrack(): void
    {
        $track = Track::next();

        Log::info("Playing {$track->path}");

        static::writeCaptionFile($track);
        static::writeQrCode($track);

        $width = config('broadcast.video_width');
        $height = config('broadcast.video_height');

        $file = Storage::disk('media')->path($track->path);
        $caption = static::NOW_PLAYING_CAPTION_PATH;

        // TODO: Review, may be able to remove? The initial call wasn't working but this seems to work well
        Process::run("set_fifo_size");

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
        
        if($result->failed())
            throw new BufferException($process, "Failed to buffer $file ({$result->exitCode()})");
    }
}
