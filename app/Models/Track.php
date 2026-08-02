<?php

namespace App\Models;

use App\Filament\Resources\Tracks\TrackResource;
use App\Observers\TrackObserver;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Kiwilan\Audio\Audio;

#[ObservedBy([TrackObserver::class])]
class Track extends Model
{
	/** @use HasFactory<\Database\Factories\TrackFactory> */
	use HasFactory;

	const SUPPORTED_EXTENSIONS = [
		'mp3',
		'ogg',
		'flac',
	];

	protected $attributes = [
		'plays' => 0,
	];

	protected $fillable = [
		'title',
		'artist',
		'path',
		'hash',
		'plays',
		'last_played_at',
	];

	public static function createFromFile(string $relative): Track
	{
		$path = Storage::disk('media')->path($relative);

		if (! file_exists($path))
			throw new InvalidArgumentException("File does not exist '$path'");

		if (! is_file($path))
			throw new InvalidArgumentException("'$path' is not a file");

		$hash = md5(file_get_contents($path));

		$audio = Audio::read($path);

		$track = Track::create([
			'path' => $relative,
			'hash' => $hash,
			'artist' => $audio->getArtist(),
			'title' => $audio->getTitle(),
		]);

		return $track;
	}

	public static function next(): Track
	{
		$track = Track::orderBy('plays')->inRandomOrder()->firstOrFail();

		$track->increment('plays', 1, [
			'last_played_at' => Carbon::now(),
		]);

		return $track;
	}

	public function votes(): HasMany
	{
		return $this->hasMany(Vote::class);
	}

	public function duplicates(): BelongsToMany
	{
		return $this->belongsToMany(Track::class, 'track_has_duplicates', 'original_id', 'duplicate_id')
			->using(TrackHasDuplicates::class)
			->withPivot('confidence');
	}

	public function originals(): BelongsToMany
	{
		return $this->belongsToMany(Track::class, 'track_has_duplicates', 'duplicate_id', 'original_id')
			->using(TrackHasDuplicates::class)
			->withPivot('confidence');
	}

	#[Scope]
	public function mostRecentlyPlayed(Builder $query): void
	{
		$query
			->whereNotNull('last_played_at')
			->orderBy('last_played_at', 'DESC')
			->limit(10);
	}

	#[Scope]
	public function hasDuplicates(Builder $query): void
	{
		$query
			->whereIn('id', fn ($query) => $query
				->select('original_id')
				->from(TrackHasDuplicates::getTableName())
			);
	}

	#[Scope]
	public function hasOriginals(Builder $query): void
	{
		$query
			->whereIn('id', fn ($query) => $query
				->select('duplicate_id')
				->from(TrackHasDuplicates::getTableName())
			);
	}

	protected function adminUrl(): Attribute
	{
		return Attribute::make(
			get: fn () => TrackResource::getUrl('view', ['record' => $this])
		);
	}

	protected function url(): Attribute
	{
		return Attribute::make(
			get: fn () => url("/tracks/{$this->hash}")
		);
	}

	protected function lastPlayedForHumans(): Attribute
	{
		// TODO: I think we can just use diffForHumans - Jippity gave me this snippet
		return new Attribute(
			get: fn () => CarbonInterval::seconds(Carbon::parse($this->last_played_at)->diffInSeconds(Carbon::now()))->cascade()->forHumans().' ago',
		);
	}

	protected function caption(): Attribute
	{
		$artist = 'Unknown Artist';
		$title = 'Unknown Title';

		if (! empty($this->artist))
			$artist = $this->artist;

		if (! empty($this->title))
			$title = $this->title;

		return new Attribute(
			get: fn () => $artist.' - '.$title
		);
	}

	// TODO: These should be query scopes really so we can sort by them
	protected function numUpVotes(): Attribute
	{
		return new Attribute(
			get: fn () => $this->votes()->whereType('up')->count()
		);
	}

	protected function numDownVotes(): Attribute
	{
		return new Attribute(
			get: fn () => $this->votes()->whereType('down')->count()
		);
	}

	public function isFileMissing(): bool
	{
		return ! Storage::disk('media')->exists($this->path);
	}
}
