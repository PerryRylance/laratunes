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
            try{
                Log::info("[$name].std$type: $output");
            }catch(BindingResolutionException $e) {
                if($e->getMessage() === 'Target class [config] does not exist.')
                {
                    // trigger_error("Caught BindingResolutionException from Log, process did not terminate cleanly", E_USER_WARNING);
                    return;
                }

                throw $e;
            }
        });
    }
}
