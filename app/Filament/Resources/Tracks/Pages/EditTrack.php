<?php

namespace App\Filament\Resources\Tracks\Pages;

use App\Filament\Resources\Tracks\TrackResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTrack extends EditRecord
{
    protected static string $resource = TrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
