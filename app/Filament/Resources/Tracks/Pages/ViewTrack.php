<?php

namespace App\Filament\Resources\Tracks\Pages;

use App\Filament\Resources\Tracks\TrackResource;
use App\Filament\Widgets\FileMissingCallout;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTrack extends ViewRecord
{
	protected static string $resource = TrackResource::class;

	protected function getHeaderActions(): array
	{
		return [
			EditAction::make(),
			DeleteAction::make(),
		];
	}

	protected function getHeaderWidgets(): array
	{
		$result = parent::getHeaderWidgets();

		if ($this->record->isFileMissing())
			$result[] = FileMissingCallout::class;

		return $result;
	}
}
