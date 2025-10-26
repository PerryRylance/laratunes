<?php

namespace App\Services;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Redis;

class FfmpegService
{
    public static function start(string $name, array $params, int $priority = 0): InvokedProcess
    {
        $lower = strtolower($name);

        return Process::timeout(false)->start(['nice', '-n', $priority, 'ffmpeg', ...$params], function (string $type, string $output) use ($name, $lower) {

            if(preg_match('/bitrate=\s*(\d+(.\d+)?)kbits\/s/', $output, $m))
                Redis::set("monitor:bitrate:$lower", $m[1]);
            else
                Log::info("[$name].std$type: $output");

        });
    }

    // private static function report(string $name, string $)
}
