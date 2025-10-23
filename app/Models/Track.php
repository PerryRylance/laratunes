<?php

namespace App\Models;

use App\Facades\Olaf;
use App\Observers\TrackObserver;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Kiwilan\Audio\Audio;
use Throwable;

#[ObservedBy([TrackObserver::class])]
class Track extends Model
{
    /** @use HasFactory<\Database\Factories\TrackFactory> */
    use HasFactory;

	const SUPPORTED_EXTENSIONS = [
		'mp3',
		'ogg',
		'flac'
	];

	protected $attributes = [
		'plays' => 0
	];

	protected $fillable = [
		'title',
		'artist',
		'path',
		'hash',
		'plays',
		'last_played_at'
	];

	public static function createFromFile(string $relative): Track
	{
		$path = Storage::disk('media')->path($relative);

		if(!file_exists($path))
			throw new InvalidArgumentException("File does not exist '$path'");

		if(!is_file($path))
			throw new InvalidArgumentException("'$path' is not a file");

		$hash = md5(file_get_contents($path));

		$audio = Audio::read($path);

		$track = Track::create([
			'path' => $relative,
			'hash' => $hash,
			'artist' => $audio->getArtist(),
			'title' => $audio->getTitle()
		]);

		return $track;
	}

	public static function next(): Track
	{
		$track = Track::orderBy('plays')->inRandomOrder()->firstOrFail();

		$track->increment('plays', 1, [
			'last_played_at' => Carbon::now()
		]);

		return $track;
	}

	public function votes(): HasMany
	{
		return $this->hasMany(Vote::class);
	}

	public function duplicates(): BelongsToMany
	{
		return $this->belongsToMany(Track::class, 'track_has_duplicates', 'original_id', 'duplicate_id');
	}

	public function originals(): BelongsToMany
	{
		return $this->belongsToMany(Track::class, 'track_has_duplicates', 'duplicate_id', 'original_id');
	}

	public function scopeMostRecentlyPlayed(Builder $query)
	{
		$query
			->whereNotNull('last_played_at')
			->orderBy('last_played_at', 'DESC')
			->limit(10);
	}

	protected function lastPlayedForHumans(): Attribute
	{
		// TODO: I think we can just use diffForHumans - Jippity gave me this snippet
		return new Attribute(
			get: fn () => CarbonInterval::seconds( Carbon::parse($this->last_played_at)->diffInSeconds( Carbon::now() ) )->cascade()->forHumans() . ' ago',
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
}
