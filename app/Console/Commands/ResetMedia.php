<?php

namespace App\Console\Commands;

use App\Facades\Olaf;
use App\Models\Track;
use App\Models\TrackHasDuplicates;
use App\Models\Vote;
use Illuminate\Console\Command;

class ResetMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-media';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the media library';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Olaf::reset();

        Vote::query()->truncate();
        TrackHasDuplicates::query()->truncate();
        Track::query()->truncate();

        $this->info('Media library reset');
    }
}
