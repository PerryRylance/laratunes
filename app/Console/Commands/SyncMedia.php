<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SyncMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-media';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prunes orphaned records from the database then discovers new media';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Artisan::call('app:prune-media');

        echo Artisan::output();

        Artisan::call('app:discover-media');

        echo Artisan::output();
    }
}
