<?php

namespace App\Filament\Widgets;

use App\Services\MonitorService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

use function PHPSTORM_META\map;

abstract class MonitorChart extends ChartWidget
{
    protected ?string $pollingInterval = '2s';

    protected function getMaximum(): int | float | null
    {
        return null;
    }

    protected function getData(): array
    {
        return [
            'labels' => array_fill(0, MonitorService::HISTORY_SIZE, '') // NB: Required for the chart to display
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        if($this->getMaximum() === null)
            return null;

        return [
            'scales' => [
                'y' => [
                    'min' => 0,
                    'max' => $this->getMaximum()
                ]
            ],
            // 'plugins' => [
            //     'annotation' => [
            //         'annotations' => [
            //             'line1' => [
            //                 'type' => 'line',
            //                 'yMin' => 60,
            //                 'yMax' => 60,
            //                 'borderColor' => 'rgb(255, 99, 132)',
            //                 // 'borderWidth' => 2,
            //             ]

            //             // 'box1' => [
            //             //     'id' => 'danger',
            //             //     'type' => 'box',
            //             //     // 'label' => [],
            //             //     'xMin' => 0,
            //             //     'xMax' => 1,
            //             //     'yMin' => 0,
            //             //     'yMax' => 1,
            //             //     // 'backgroundColor' => 'rgba(255, 99, 132, 0.25)'

            //             //     // 'type' => 'box',
            //             //     // 'xMin' => 0,
            //             //     // 'xMax' => MonitorService::HISTORY_SIZE,
            //             //     // 'yMin' => 95,
            //             //     // 'yMax' => 100,
            //             //     // 'backgroundColor' => 'rgba(255, 0, 0, 0.75)',
            //             // ]
            //         ]
            //     ]
            // ]
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
