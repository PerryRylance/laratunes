<?php

namespace App\Filament\Resources\Tracks\Pages;

use App\Exceptions\YtDlpException;
use App\Facades\YtDlp;
use App\Filament\Resources\Tracks\TrackResource;
use App\Models\Track;
use Dom\HTMLDocument;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateTrackFromUrl extends CreateRecord
{
	protected static string $resource = TrackResource::class;

	public function form(Schema $schema): Schema
	{
		return $this->defaultForm($schema)
			->components([
				TextInput::make('url')
					->label('YouTube URL')
					->url()
					->required(),
				TextInput::make('artist')
					->required(),
				TextInput::make('title')
					->required(),
			]);
	}

	protected function handleRecordCreation(array $data): Model
	{
		$uuid = (string) Str::uuid();
		$relativePath = "$uuid.mp3";
		$outputTemplate = Storage::disk('media')->path("$uuid.%(ext)s");

		try
		{
			YtDlp::download($data['url'], $outputTemplate);
		}
		catch (YtDlpException $exception)
		{
			$this->failDownload($exception->getMessage(), $exception->output);
		}

		if (! Storage::disk('media')->exists($relativePath))
			$this->failDownload('yt-dlp finished but the downloaded file could not be found.', '');

		$hash = md5_file(Storage::disk('media')->path($relativePath));

		$existing = Track::whereHash($hash)->first();

		if ($existing)
		{
			Storage::disk('media')->delete($relativePath);

			$this->failDownload("This track already exists as {$existing->caption}.", '');
		}

		$track = Track::create([
			'path' => $relativePath,
			'artist' => $data['artist'],
			'title' => $data['title'],
		]);

		$this->warnAboutDuplicates($track);

		return $track;
	}

	// NB: Sends the failure notification (with a "Download log" link when raw yt-dlp output is
	// available) then throws Halt, which Filament's CreateRecord::create() catches to abort the
	// save and re-render the same form - keeping the url/artist/title fields exactly as submitted.
	private function failDownload(string $reason, string $log): never
	{
		$document = HTMLDocument::createEmpty();

		$container = $document->createElement('div');
		$document->appendChild($container);

		$p = $document->createElement('p');
		$p->append($document->createTextNode($reason));
		$container->appendChild($p);

		if ($log !== '')
		{
			$a = $document->createElement('a');

			$a->setAttribute('download', 'yt-dlp-log.txt');
			$a->setAttribute('href', 'data:text/plain;charset=utf-8,'.rawurlencode($log));
			$a->append($document->createTextNode('Download log'));

			$container->appendChild($a);
		}

		Notification::make()
			->danger()
			->title('Download failed')
			->body($document->saveHtml($container))
			->persistent()
			->send();

		throw new Halt;
	}

	// NB: Track::create() above already triggered TrackObserver::created(), which fingerprinted
	// the download and attached it as a duplicate of any matching originals - this just checks
	// whether that happened and, if so, warns the user the same way the upload form does.
	private function warnAboutDuplicates(Track $track): void
	{
		$originals = $track->originals()->get()->sortByDesc(fn (Track $original) => $original->pivot->confidence ?? 0);

		if ($originals->isEmpty())
			return;

		$document = HTMLDocument::createEmpty();

		$ul = $document->createElement('ul');
		$document->appendChild($ul);

		foreach ($originals as $original)
		{
			$li = $document->createElement('li');
			$a = $document->createElement('a');

			$a->setAttribute('target', '_blank');
			$a->setAttribute('href', $original->adminUrl);
			$a->append($document->createTextNode($original->caption));

			$li->appendChild($a);
			$ul->appendChild($li);
		}

		Notification::make()
			->warning()
			->title($originals->count() > 1 ? 'Possible duplicate tracks found' : 'Possible duplicate track found')
			->body($document->saveHtml($ul))
			->persistent()
			->send();
	}
}
