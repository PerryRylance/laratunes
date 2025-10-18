<?php

namespace App\Filament\Resources\Tracks;

use App\Filament\Resources\Tracks\Pages\CreateTrack;
use App\Filament\Resources\Tracks\Pages\EditTrack;
use App\Filament\Resources\Tracks\Pages\ListTracks;
use App\Filament\Resources\Tracks\Pages\ViewTrack;
use App\Filament\Resources\Tracks\Schemas\TrackForm;
use App\Filament\Resources\Tracks\Schemas\TrackInfolist;
use App\Filament\Resources\Tracks\Tables\TracksTable;
use App\Models\Track;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TrackResource extends Resource
{
    protected static ?string $model = Track::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMusicalNote;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return TrackForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TrackInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TracksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTracks::route('/'),
            'create' => CreateTrack::route('/create'),
            'view' => ViewTrack::route('/{record}'),
            'edit' => EditTrack::route('/{record}/edit'),
        ];
    }
}
