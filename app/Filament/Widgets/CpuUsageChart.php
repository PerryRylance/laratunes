<?php

namespace App\Filament\Widgets;

use App\Facades\Monitor;

class CpuUsageChart extends MonitorChart
{
    protected ?string $heading = 'CPU Usage';

    protected function getData(): array
    {
        return [
            ...parent::getData(),
            'datasets' => [
                [
                    'label' => '%',
                    'data' => Monitor::list('monitor:cpu'),
                ],
            ],
        ];
    }

    protected function getMaximum(): int|float|null
    {
        return 100;
    }
}
