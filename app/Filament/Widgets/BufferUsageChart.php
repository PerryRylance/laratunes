<?php

namespace App\Filament\Widgets;

use App\Facades\Monitor;
use App\Services\MonitorService;
use Filament\Support\Colors\Color;

class BufferUsageChart extends MonitorChart
{
    protected ?string $heading = 'Buffer Usage';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'kB',
                    'data' => array_map(fn($bytes) => $bytes / 1024, Monitor::list('monitor:buffer')),
                ],
            ],
            'labels' => array_fill(0, MonitorService::HISTORY_SIZE, '') // NB: Required for the chart to display
        ];
    }

    protected function getMaximum(): int|float
    {
        return 1024;
    }

    protected function getType(): string
    {
        return 'line';
    }
}
