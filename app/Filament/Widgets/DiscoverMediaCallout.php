<?php

namespace App\Filament\Widgets;

use App\Jobs\CreateTrackJob;
use App\Models\Track;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DiscoverMediaCallout extends Widget
{
	protected string $view = 'filament.widgets.discover-media-callout';

	protected array|string|int $columnSpan = 'full';

	protected static bool $isLazy = false;

	public int $numQueuedJobs;

	public int $numProcessedFiles;

	public int $numFiles;

	public int $numTracks;

	public static function canView(): bool
	{
		$widget = new static();
		$widget->init();

		return $widget->numQueuedJobs > 0 || $widget->numTracks === 0;
	}

	private function init(): void
	{
		$this->numTracks = Track::count();

		if (env('QUEUE_CONNECTION') !== 'database')
			throw new RuntimeException('Expected queue connection to be "database"');

		// NB: Bit of a hack that relies on the jobs being in the database, but works well. They should be persisted there after all.
		$this->numQueuedJobs = DB::table('jobs')->where('payload', 'LIKE', '%'.addslashes(CreateTrackJob::class).'%')->count();

		if ($this->numQueuedJobs > 0)
		{
			$this->numFiles = $this->files()->count();
			$this->numProcessedFiles = $this->numFiles - $this->numQueuedJobs;
		}
	}

	private function files(): Collection
	{
		return (new Collection(Storage::disk('media')->allFiles()))
			->filter(fn (string $file) => in_array(
				strtolower(pathinfo($file, PATHINFO_EXTENSION)), Track::SUPPORTED_EXTENSIONS
			))
			->values();
	}

	public function discover(): void
	{
		foreach ($this->files() as $path)
			CreateTrackJob::dispatch($path);
	}

	public function render(): View
	{
		$this->init();

		return parent::render();
	}
}
