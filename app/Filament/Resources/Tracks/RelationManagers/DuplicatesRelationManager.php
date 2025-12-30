<?php

namespace App\Filament\Resources\Tracks\RelationManagers;

use App\Filament\Resources\Tracks\TrackResource;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DuplicatesRelationManager extends RelationManager
{
	protected static string $relationship = 'duplicates';

	protected static ?string $inverseRelationship = 'originals';

	protected static ?string $relatedResource = TrackResource::class;

	public static function getTabComponent(Model $ownerRecord, string $pageClass): Tab
	{
		return Tab::make('Duplicates')
			->key('duplicates')
			->badge($ownerRecord->duplicates()->count())
			->badgeColor('warning')
			->badgeTooltip('Original tracks which have similar fingerprints to this track')
			->icon('heroicon-m-document-duplicate');
	}

	public function table(Table $table): Table
	{
		return $table
			->heading('Duplicates')
			->headerActions([
				AttachAction::make(),
			])
			->recordActions([
				DetachAction::make(),
			])
			->toolbarActions([
				BulkActionGroup::make([
					DetachBulkAction::make(),
				]),
			]);
	}
}
