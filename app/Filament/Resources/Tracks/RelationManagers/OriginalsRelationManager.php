<?php

namespace App\Filament\Resources\Tracks\RelationManagers;

use App\Filament\Resources\Tracks\TrackResource;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OriginalsRelationManager extends DuplicatesRelationManager
{
	protected static string $relationship = 'originals';

	protected static ?string $relatedResource = TrackResource::class;

	public static function getTabComponent(Model $ownerRecord, string $pageClass): Tab
	{
		return Tab::make('Originals')
			->key('originals')
			->badge($ownerRecord->originals()->count())
			->badgeColor('warning')
			->badgeTooltip('Duplicate tracks which have similar fingerprints to this track')
			->icon('heroicon-m-document');
	}

	public function table(Table $table): Table
	{
		return parent::table($table)
			->heading('Originals');
	}
}
