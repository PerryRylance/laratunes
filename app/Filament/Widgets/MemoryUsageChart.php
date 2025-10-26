<?php

namespace App\Filament\Widgets;

use App\Facades\Monitor;

class MemoryUsageChart extends MonitorChart
{
    protected ?string $heading = 'Memory Usage';

    protected function getData(): array
    {
        return [
            ...parent::getData(),
            'datasets' => [
                [
                    'label' => 'GB',
                    'data' => array_map(fn($mb) => $mb / 1024, Monitor::list('monitor:memory')),
                ],
            ]
        ];
    }

    protected function getMaximum(): int|float
    {
        return Monitor::getTotalMemory() / 1024;
    }

    protected function getType(): string
    {
        return 'line';
    }
}
