<?php

namespace App\Services;

use DateTime;
use Exception;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Redis;
use LogicException;

class MonitorService
{
    const HISTORY_SIZE = 30;
    const INTERVAL_SECONDS = 2;

    public static function update(): void
    {
        $process = Process::run('top -bn1');
        $output = $process->output();

        static::storeCpuUsage($output);
        static::storeMemoryUsage($output);
        static::storeFifoUsage();
        static::storeTransmissionBitrate();
        static::storeUpdatedAt();
    }

    public static function list(string $key): array
    {
        $value = Redis::lrange($key, 0, -1);

        switch($key)
        {
            case 'monitor:cpu':
            case 'monitor:memory':
            case 'monitor:bitrate':
                $value = array_map('floatval', $value);
                break;
            
            case 'monitor:buffer':
                $value = array_map('intval', $value);
                break;
            
            default:
                throw new LogicException();
        }

        if(!$value)
            return [];

        return array_reverse($value);
    }

    public static function getTotalMemory(): ?float
    {
        return Redis::get('monitor:memory_total');
    }

    private static function pushAndTrim(string $key, $arg): void
    {
        Redis::pipeline(function ($pipe) use ($key, $arg) {

            $pipe->lpush($key, $arg);
            $pipe->ltrim($key, 0, static::HISTORY_SIZE - 1);

        });
    }

    private static function storeCpuUsage(string $output): void
    {
        if(!preg_match('/(\d+\.\d) id/', $output, $m))
            throw new Exception('Failed to match CPU idle in top output');

        $usage = 100 - floatval($m[1]);

        static::pushAndTrim('monitor:cpu', $usage);
    }

    private static function storeMemoryUsage(string $output): void
    {
        if(!preg_match('/MiB Mem\s*:\s*(\d+\.\d) total,\s*\d+\.\d free,\s*(\d+\.\d) used/', $output, $m))
            throw new Exception('Failed to match CPU idle in top output');

        $total = floatval($m[1]);
        $used = floatval($m[2]);

        Redis::set('monitor:memory_total', $total);

        static::pushAndTrim('monitor:memory', $used);
    }

    private static function storeFifoUsage(): void
    {
        $process = Process::run('get_fifo_bytes_available');
        $output = $process->output();
        $bytes = (int)$output;

        static::pushAndTrim('monitor:buffer', $bytes);
    }

    private static function storeTransmissionBitrate(): void
    {
        $bitrate = Redis::get('monitor:bitrate:transmitter') ?? 0;

        static::pushAndTrim('monitor:bitrate', $bitrate);
    }

    private static function storeUpdatedAt(): void
    {
        Redis::set('monitor:updated_at', (new DateTime)->format(DateTime::ATOM));
    }
}
