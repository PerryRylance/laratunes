<?php

namespace App\Console\Commands;

use App\Facades\Buffer;
use App\Facades\Fifo;
use App\Facades\Transmission;
use App\Services\BufferService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Fork\Exceptions\CouldNotManageTask;

class StartBroadcast extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:start-broadcast';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Starts broadcasting';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		Log::info('Starting broadcast');

		// TODO: Doesn't handle the transmission being cut well, need to know please
		while (true)
		{
			try
			{
				Log::info('Creating "now playing" buffer');
				Fifo::create(BufferService::NOW_PLAYING_BUFFER_PATH);

				Log::info('Beginning buffer loop, transmission and monitoring...');

				Concurrency::driver('fork')->run([
					fn () => Buffer::loop(),
					fn () => Transmission::begin(),
					fn () => Artisan::call('app:monitor'),
				]);

				Log::info('All processes launched');
			}
			catch (CouldNotManageTask)
			{
				$this->fail('Broadcast stopped unexpectedly, check the logs for more information');

				if (RateLimiter::tooManyAttempts('resume-broadcast', 10))
					exit(1);

				RateLimiter::hit('resume-broadcast', 60);
			}
		}

		// TODO: Trap sigterm? Differentiate between OS requested shutdown and processes ended unexpectedly?
	}
}
