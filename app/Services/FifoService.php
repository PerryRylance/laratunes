<?php

namespace App\Services;

use App\Exceptions\BufferException;
use Illuminate\Support\Facades\Process;

class FifoService
{
    private static $keepOpenHandle;
    private static int $capacity;

    public static function create(string $file): void
    {
        $result = Process::run("rm -f $file && mkfifo $file");

        if($result->failed())
        {
            $output = trim($result->errorOutput());
            $message = "Failed to create now playing buffer ($output)";

            throw new BufferException($message);
        }
        
        // NB: Hangs
        // static::$keepOpenHandle = fopen($file, 'w');

        // if(static::$keepOpenHandle === false)
        //     throw new BufferException('Failed to create keep-open');

        $result = Process::run("set_fifo_size");
        $output = $result->output();

        if($result->failed())
            throw new BufferException("Failed to set FIFO size: {$output} ({$result->exitCode()})");

        if(!preg_match('/FIFO buffer size successfully set to (\d+) bytes/', $output, $m))
            throw new BufferException("Failed to match created FIFO size in $output");

        static::$capacity = (int)$m[1];
    }

    public static function capacity(): int
    {
        return static::$capacity;
    }

    public static function usage(): int
    {
        return (int)Process::run('get_fifo_bytes_available')->output();
    }
}
