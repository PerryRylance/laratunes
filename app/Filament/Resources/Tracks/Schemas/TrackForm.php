<?php

namespace App\Filament\Resources\Tracks\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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
                    ->required()
            ]);
    }
}
