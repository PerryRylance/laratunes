<?php

namespace App\Filament\Resources\Tracks\Pages;

use App\Filament\Resources\Tracks\TrackResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTrack extends CreateRecord
{
	protected static string $resource = TrackResource::class;

	protected function handleRecordCreation(array $data): Model
	{
		$path = $data['attachment'];

		unset($data['attachment']);

		$data['path'] = $path;

		return static::getModel()::create($data);
	}
}
