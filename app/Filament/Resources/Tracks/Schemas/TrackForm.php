<?php

namespace App\Filament\Resources\Tracks\Schemas;

use App\Facades\Olaf;
use App\Models\Track;
use Closure;
use Dom\HTMLDocument;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class TrackForm
{
	public static function configure(Schema $schema): Schema
	{
		$hasDuplicates = $hasOriginals = false;

		if ($schema->model instanceof Track)
		{
			$hasDuplicates = $schema->model->duplicates()->exists();
			$hasOriginals = $schema->model->originals()->exists();
		}

		return $schema
			->components([
				FileUpload::make('attachment')
					->disk('media')
					->acceptedFileTypes([
						'audio/mpeg',
						'audio/ogg',
						'audio/flac',
					])
					->preserveFilenames()
					->hiddenOn([Operation::View, Operation::Edit])
					->required()
					->live()
					->afterStateUpdated(function ($state): void {

						$file = is_array($state) ? Arr::first($state) : $state;

						if (! $file instanceof TemporaryUploadedFile)
						return;

						static::warnAboutDuplicates($file);

					})
					->rules([
						fn (): Closure => function (string $attribute, $value, Closure $fail) {

							$query = Track::whereHash(md5_file($value->getRealPath()));

							if (! $query->exists())
							return;

							$existing = $query->firstOrFail()->path;

							$fail("This track already exists at $existing");

						},
					])
					->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): string {

						$storeMethod = $component->getVisibility() === 'public' ? 'storePubliclyAs' : 'storeAs';

						$filename = $file->getClientOriginalName();

						if (Storage::disk('media')->exists($filename))
						{
							$suffixNumber = 0;

							do
							{

								$suffixNumber++;
								$modifiedFilename = "$filename ($suffixNumber)";

							}
							while (Storage::disk('media')->exists($modifiedFilename));

							$filename = $modifiedFilename;
						}

						return $file->{$storeMethod}($component->getDirectory(), $filename, $component->getDiskName());

					}),
				TextInput::make('title')
					->hiddenOn(Operation::Create),
				TextInput::make('artist')
					->hiddenOn(Operation::Create),
				TextEntry::make('path')
					->label('Path')
					->hiddenOn([Operation::Create]),
				ViewField::make('audio')
					->view('filament.forms.fields.audio-player')
					->hiddenOn([Operation::Create]),
			]);
	}

	private static function warnAboutDuplicates(TemporaryUploadedFile $file): void
	{
		// NB: Olaf::query() matches records against the exact filename we pass it, and Track paths
		// are always flat basenames - so the scratch copy must live at the media disk root, not a subdirectory.
		$scratchPath = '.duplicate-check-'.Str::uuid().'.'.$file->getClientOriginalExtension();

		try
		{
			$file->storeAs('', $scratchPath, 'media');

			$results = Olaf::query($scratchPath);
		}
		catch (Throwable $exception)
		{
			// NB: Fail open - the duplicate pre-check is a nicety, it shouldn't block uploading if Olaf is unreachable.
			Log::warning('Olaf duplicate pre-check failed: '.$exception->getMessage());

			return;
		}
		finally
		{
			Storage::disk('media')->delete($scratchPath);
		}

		if ($results->items->isEmpty())
		return;

		$confidenceByPath = $results->items->pluck('confidence', 'file');

		$matches = Track::whereIn('path', $confidenceByPath->keys())
			->get()
			->sortByDesc(fn (Track $track) => $confidenceByPath[$track->path] ?? 0);

		if ($matches->isEmpty())
		return;

		$document = HTMLDocument::createEmpty();

		$ul = $document->createElement('ul');
		$document->appendChild($ul);

		foreach ($matches as $track)
		{
			$li = $document->createElement('li');

			$a = $document->createElement('a');

			$a->setAttribute('target', '_blank');
			$a->setAttribute('href', $track->adminUrl);
			$a->append($document->createTextNode($track->caption));

			$li->appendChild($a);
			$ul->appendChild($li);
		}

		Notification::make()
			->warning()
			->title($matches->count() > 1 ? 'Possible duplicate tracks found' : 'Possible duplicate track found')
			->body($document->saveHtml($ul))
			->persistent()
			->send();
	}
}
