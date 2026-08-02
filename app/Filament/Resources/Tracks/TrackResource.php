<?php

namespace App\Filament\Resources\Tracks;

use App\Filament\Resources\Tracks\Pages\CreateTrack;
use App\Filament\Resources\Tracks\Pages\EditTrack;
use App\Filament\Resources\Tracks\Pages\ListTracks;
use App\Filament\Resources\Tracks\Pages\ViewTrack;
use App\Filament\Resources\Tracks\Schemas\TrackForm;
use App\Filament\Resources\Tracks\Schemas\TrackInfolist;
use App\Filament\Resources\Tracks\Tables\TracksTable;
use App\Filament\Widgets\FileMissingCallout;
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
			'duplicates' => RelationManagers\DuplicatesRelationManager::class,
			'originals' => RelationManagers\OriginalsRelationManager::class,
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

	public static function getWidgets(): array
	{
		// NB: FileMissingCallout is only ever rendered conditionally from ViewTrack::getHeaderWidgets(),
		// but it still needs to be listed here - this is what makes Filament pre-register it as a
		// Livewire component alias. Without that, Livewire throws ComponentNotFoundException whenever
		// it needs to rehydrate this widget by name instead of mounting it fresh from the class.
		return [
			FileMissingCallout::class,
		];
	}
}
