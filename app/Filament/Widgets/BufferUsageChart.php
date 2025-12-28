<?php

namespace App\Filament\Widgets;

use App\Facades\Monitor;
use App\Services\MonitorService;
use Filament\Support\Colors\Color;

class BufferUsageChart extends MonitorChart
{
    protected ?string $heading = 'Buffer Usage';
    protected static ?int $sort = 1;

    protected function getData(): array
    {
        return [
            ...parent::getData(),
            'datasets' => [
                [
                    'label' => 'kB',
                    'data' => array_map(fn($bytes) => $bytes / 1024, Monitor::list('monitor:buffer')),
                ],
            ]
        ];
    }

    protected function getMaximum(): int|float|null
    {
        return 1024;
    }

    protected function getType(): string
    {
        return 'line';
    }
}
