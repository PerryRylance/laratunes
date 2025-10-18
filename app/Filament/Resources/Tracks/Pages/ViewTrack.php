<?php

namespace App\Filament\Resources\Tracks\Pages;

use App\Filament\Resources\Tracks\TrackResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTrack extends ViewRecord
{
    protected static string $resource = TrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
