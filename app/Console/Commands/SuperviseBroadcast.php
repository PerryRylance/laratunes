<?php

namespace App\Console\Commands;

use App\Facades\BroadcastSupervisor;
use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SuperviseBroadcast extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:supervise-broadcast {--once}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Watches the broadcast from outside the process and restarts it if it has been unhealthy for too long';

	private ?DateTimeInterface $downSince = null;

	public function handle(): void
	{
		Log::info('Broadcast watchdog started');

		while (true)
		{
			$this->check();

			if ($this->option('once'))
			break;

			sleep(config('broadcast.watchdog_check_interval'));
		}
	}

	private function check(): void
	{
		if (BroadcastSupervisor::isFullyRunning())
		{
			$this->downSince = null;

			return;
		}

		if (! $this->downSince)
		{
			$this->downSince = now();

			Log::warning('Broadcast watchdog: broadcast is not fully running, will intervene if this persists', BroadcastSupervisor::diagnostics());
		}

		// NB: Carbon's diffInSeconds() is signed (not absolute) - $this->downSince->diffInSeconds(now())
		// gives the (positive) number of seconds elapsed since it was set, the other way round gives a
		// negative number and this check would never trip
		if ($this->downSince->diffInSeconds(now()) < config('broadcast.watchdog_grace_period'))
		return;

		$this->downSince = null;

		Log::error('Broadcast watchdog: broadcast has been down for over '.config('broadcast.watchdog_grace_period').'s, restarting it', BroadcastSupervisor::diagnostics());

		try
		{
			BroadcastSupervisor::start();

			Log::info('Broadcast watchdog: restart succeeded');
		}
		catch (Throwable $e)
		{
			Log::critical('Broadcast watchdog: restart failed, human intervention required: '.$e->getMessage(), [
				'exception' => $e,
				...BroadcastSupervisor::diagnostics(),
			]);
		}
	}
}
