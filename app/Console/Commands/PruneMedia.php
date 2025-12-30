<?php

namespace App\Console\Commands;

use App\Models\Track;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PruneMedia extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:prune-media';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Scans the media path and removes any orphaned records from the library';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		$chunkSize = 100;
		$numTracksPruned = 0;

		$bar = $this->output->createProgressBar(Track::count());
		$bar->setFormat(' %current%/%max% [%bar%] %percent%% <info>%message%</info>');
		$bar->setMessage('Starting discovery...');
		$bar->start();

		Track::select(['id', 'path'])->chunk($chunkSize, function (Collection $tracks) use (&$numTracksPruned, $bar) {
			foreach ($tracks as $track)
			{
				if (Storage::disk('media')->exists($track->path))
				continue;

				$track->delete();

				$bar->setMessage("Pruned {$track->path}");
				$numTracksPruned++;
			}
		});

		$bar->finish();

		echo PHP_EOL;

		$numTracks = Track::query()->count();

		$this->info("Pruned $numTracksPruned tracks, new total is $numTracks");
	}
}
