<?php

namespace App\Console\Commands;

use App\Exceptions\TransmissionException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Facades\Buffer;
use App\Facades\Transmission;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Concurrency;
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
        Log::info("Starting broadcast");

        // TODO: Doesn't handle the transmission being cut well, need to know please

        try{
            Concurrency::driver('fork')->run([
                fn() => Buffer::loop(),
                fn() => Transmission::begin(),
                fn() => Artisan::call('app:monitor')
            ]);
        }catch(CouldNotManageTask) {
            $this->fail('Broadcast stopped unexpectedly, check the logs for more information');
        }

        // TODO: Trap sigterm? Differentiate between OS requested shutdown and processes ended unexpectedly?
    }
}
