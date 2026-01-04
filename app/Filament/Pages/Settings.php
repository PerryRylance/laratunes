<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Settings extends Page implements HasSchemas
{
	use InteractsWithSchemas;

	protected string $view = 'filament.pages.settings';

	protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

	protected static ?int $navigationSort = PHP_INT_MAX;

	public ?array $data = [];

	public function mount(): void
	{
		$this->form->fill(Setting::getAllAsAssociativeArray());
	}

	public function form(Schema $schema): Schema
	{
		return $schema
			->components([
				TextInput::make(Setting::BROADCAST_VIDEO_WIDTH)
					->label('Video width')
					->hint('YouTube recommends at least 1280')
					->numeric()
					->minValue(426)
					->maxValue(7680)
					->required(),
				TextInput::make(Setting::BROADCAST_VIDEO_HEIGHT)
					->label('Video height')
					->hint('YouTube recommends at least 720')
					->numeric()
					->minValue(240)
					->maxValue(4320)
					->required(),
				FileUpload::make(Setting::BROADCAST_BACKGROUND_PATH)
					->disk('media')
					->acceptedFileTypes([
						'image/jpeg',
						'image/png',
						'image/webp',
						'video/mp4',
						'video/mpeg',
						'video/ogg',
						'video/webm',
						'video/x-matroska',
					]),
				TextInput::make(Setting::STREAM_URL)
					->label('Stream URL')
					->url()
					->rules(fn (): Closure => function (string $attribute, $value, Closure $fail) {
						if (preg_match('/^rtmp:\/\//i', $value))
						return;

						$fail('Must be an rtmp:// URL');
					})
					->required(),
				TextInput::make(Setting::STREAM_KEY)
					->label('Stream key')
					->required(),
				// TODO: Nightbot API key
				Action::make('save')
					->action(fn () => $this->save()),
			])
			->statePath('data');
	}

	public function save(): void
	{
		$data = $this->form->getState();

		foreach ($data as $name => $value)
			Setting::value($name, $value);

		Notification::make()
			->success()
			->title('Settings updated!')
			->send();
	}
}
