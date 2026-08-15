<?php

namespace App\Filament\Resources\Tracks\Pages;

use App\Facades\YtDlp;
use App\Filament\Resources\Tracks\TrackResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTracks extends ListRecords
{
	protected static string $resource = TrackResource::class;

	protected function getHeaderActions(): array
	{
		return [
			CreateAction::make(),
			Action::make('createFromUrl')
				->label('Create from URL')
				->url(fn () => CreateTrackFromUrl::getUrl())
				->visible(fn () => YtDlp::installed()),
		];
	}
}
