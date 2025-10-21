<?php

namespace App\Filament\Resources\Tracks\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;

class TrackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('attachment')
                    ->disk('media')
                    ->acceptedFileTypes([
                        'audio/mpeg',
                        'audio/ogg',
                        'audio/flac'
                    ])
                    ->preserveFilenames()
                    ->hiddenOn([Operation::View, Operation::Edit])
                    ->required(),
                TextInput::make('title')
                    ->hiddenOn(Operation::Create),
                TextInput::make('artist')
                    ->hiddenOn(Operation::Create),
            ]);
    }
}
