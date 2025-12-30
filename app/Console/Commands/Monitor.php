<?php

namespace App\Console\Commands;

use App\Facades\Monitor as Facade;
use App\Services\MonitorService;
use Illuminate\Console\Command;

class Monitor extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:monitor {--once}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Monitors system health and performance';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		while (true)
		{
			Facade::update();

			if ($this->option('once'))
			break;

			sleep(MonitorService::INTERVAL_SECONDS);
		}
	}
}
