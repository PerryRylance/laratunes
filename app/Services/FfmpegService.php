<?php

namespace App\Services;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class FfmpegService
{
    public static function start(string $name, array $params): InvokedProcess
    {
        return Process::timeout(false)->start(['ffmpeg', ...$params], function (string $type, string $output) use ($name) {

            // if(preg_match('/bitrate=\s*(\d+\(.\d+)?)kbits\/s/', $output, $m))


            Log::info("[$name].std$type: $output");


        });
    }

    // private static function report(string $name, string $)
}
