<?php

namespace App\Filament\Widgets;

use App\Facades\Monitor;
use Filament\Support\RawJs;

class TransmissionBitrateChart extends MonitorChart
{
	protected ?string $heading = 'Transmission Bitrate';

	protected function getData(): array
	{
		return [
			...parent::getData(),
			'datasets' => [
				[
					'label' => 'kbits/s',
					'data' => Monitor::list('monitor:bitrate'),
				],
			],
		];
	}

	protected function getOptions(): array|RawJs|null
	{
		return [
			'scales' => [
				'y' => [
					'min' => 0,
				],
			],
		];
	}
}
