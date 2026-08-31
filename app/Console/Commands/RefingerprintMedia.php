<?php

namespace App\Console\Commands;

use App\Facades\Olaf;
use App\Models\Track;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class RefingerprintMedia extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:refingerprint';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Resets the Olaf fingerprint database and re-fingerprints every existing track, without touching track/vote/duplicate records';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		$chunkSize = 50;
		$numTracksFingerprinted = 0;

		Olaf::reset();

		$bar = $this->output->createProgressBar(Track::count());
		$bar->setFormat(' %current%/%max% [%bar%] %percent%% <info>%message%</info>');
		$bar->setMessage('Starting...');
		$bar->start();

		Track::select(['id', 'path'])->orderBy('id')->chunk($chunkSize, function (Collection $tracks) use (&$numTracksFingerprinted, $bar) {
			foreach ($tracks as $track)
			{
				Olaf::fingerprint($track->path);

				$bar->setMessage("Fingerprinted {$track->path}");
				$bar->advance();
				$numTracksFingerprinted++;
			}
		});

		$bar->finish();

		echo PHP_EOL;

		$this->info("Fingerprinted $numTracksFingerprinted tracks");
	}
}
