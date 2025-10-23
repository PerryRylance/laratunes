<?php

namespace App\Console\Commands;

use App\Models\Track;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DiscoverMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:discover-media';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans the media path for new media and adds it to the library';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // TODO: Might be quicker to insert without Olaf then use Olaf's dedupe command rather than letting it query individually

        $files = (new Collection(Storage::disk('media')->allFiles()))
            ->filter(fn (string $file) => in_array( 
                strtolower(pathinfo($file, PATHINFO_EXTENSION)), Track::SUPPORTED_EXTENSIONS 
            ))
            ->values();

        $chunkSize = 100;
        $numTracksDiscovered = 0;

        $bar = $this->output->createProgressBar(count($files));
        $bar->setFormat(' %current%/%max% [%bar%] %percent%% <info>%message%</info>');
        $bar->setMessage('Starting discovery...');
		$bar->start();

        foreach($files->chunk($chunkSize) as $chunk)
		{
            $map = array_combine(
                $chunk->toArray(),
                array_fill(0, $chunk->count(), true)
            );

            $existing = Track::select('path')
                ->whereIn('path', $chunk)
                ->get();

            foreach($existing as $track)
            {
                unset($map[$track->path]);
                $bar->advance();
            }

            $discovered = array_keys($map);

            foreach($discovered as $file)
            {
                $bar->setMessage("Adding $file");

                $track = Track::createFromFile($file);

                foreach($track->originals as $original)
                    $this->warn(PHP_EOL . "{$file} may be a duplicate of {$original->file}");                

                $bar->advance();
            }

            $numTracksDiscovered += count($discovered);
        }

        $bar->finish();

        echo PHP_EOL;

        $numTracks = Track::query()->count();

        if($numTracks === 0)
            return $this->error("There are no tracks in the library");

        $this->info("Discovered $numTracksDiscovered new tracks, new total is $numTracks");
    }
}
