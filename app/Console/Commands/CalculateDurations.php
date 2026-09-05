<?php

namespace App\Console\Commands;

use App\Facades\Duration;
use App\Models\Track;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CalculateDurations extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:calculate-durations';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Calculates and stores the duration of every track with a duration of zero';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		$tracks = Track::where('duration', 0)->select(['id', 'path'])->get();

		$bar = $this->output->createProgressBar($tracks->count());
		$bar->setFormat(' %current%/%max% [%bar%] %percent%% <info>%message%</info>');
		$bar->setMessage('Starting...');
		$bar->start();

		foreach ($tracks as $track)
		{
			$path = Storage::disk('media')->path($track->path);

			$track->duration = Duration::getDurationFromFile($path);
			$track->save();

			$bar->setMessage("Calculated duration for {$track->path}");
			$bar->advance();
		}

		$bar->finish();

		echo PHP_EOL;

		Duration::invalidateCachedTrackTotalDuration();

		$this->info("Calculated duration for {$tracks->count()} tracks");
	}
}
